<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Mensage;
use App\Entity\Sala;
use App\Repository\UserRepository;
use App\Repository\MensageRepository;
use App\Repository\SalaRepository;
use App\Service\GeoLocationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private GeoLocationService $geoLocationService,
        private MensageRepository $mensageRepository,
        private SalaRepository $salaRepository
    ) {}

    /**
     * Login endpoint - Autentica usuario y genera token
     */
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['correo']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Se requieren correo y password'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar usuario por correo
        $user = $this->userRepository->findOneBy(['correo' => $data['correo']]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar contraseña
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generar token aleatorio
        $token = bin2hex(random_bytes(32));

        // Actualizar usuario: marcar estado = true y guardar token
        $user->setEstado(true);
        $user->setTokenAutenticacion($token);
        $user->setFechaInicioSesion(new \DateTime()); // Guardar fecha de inicio de sesión

        // Guardar coordenadas si se proporcionan
        if (isset($data['latitud']) && isset($data['longitud'])) {
            $user->setLatitud($data['latitud']);
            $user->setLongitud($data['longitud']);
            $user->setFechaActualizacionUbicacion(new \DateTime());
        }

        // Persistir y hacer flush para guardar cambios
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'token' => $token,
            'data' => [
                'id' => $user->getId(),
                'user' => [
                    'nombre' => $user->getNombre(),
                    'correo' => $user->getCorreo()
                ]
            ]
        ]);
    }

    /**
     * Logout endpoint - Invalida el token y elimina conversaciones privadas
     */
    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar todas las salas privadas del usuario
        $salasPrivadas = $this->salaRepository->createQueryBuilder('s')
            ->innerJoin('s.usuarios', 'u')
            ->where('u.id = :userId')
            ->andWhere('s.tipo = :tipo')
            ->setParameter('userId', $user->getId())
            ->setParameter('tipo', 'privada')
            ->getQuery()
            ->getResult();

        // Procesar cada sala privada
        $salasEliminadas = 0;
        foreach ($salasPrivadas as $sala) {
            // Eliminar todos los mensajes del usuario en esta sala
            $mensajes = $this->mensageRepository->createQueryBuilder('m')
                ->where('m.sala = :sala')
                ->andWhere('m.autor = :usuario')
                ->setParameter('sala', $sala)
                ->setParameter('usuario', $user)
                ->getQuery()
                ->getResult();

            foreach ($mensajes as $mensaje) {
                $this->entityManager->remove($mensaje);
            }

            // Verificar si hay otros usuarios activos en la sala
            $otrosUsuariosActivos = false;
            foreach ($sala->getUsuarios() as $participante) {
                if ($participante->getId() !== $user->getId() && $participante->isEstado()) {
                    $otrosUsuariosActivos = true;
                    break;
                }
            }

            // Si no hay otros usuarios activos, eliminar toda la sala
            if (!$otrosUsuariosActivos) {
                // Eliminar todos los mensajes restantes de la sala
                $mensajesRestantes = $this->mensageRepository->findBy(['sala' => $sala]);
                foreach ($mensajesRestantes as $mensaje) {
                    $this->entityManager->remove($mensaje);
                }

                // Eliminar la sala
                $this->entityManager->remove($sala);
                $salasEliminadas++;
            }
        }

        // Marcar estado = false y limpiar token y ubicación
        $user->setEstado(false);
        $user->setTokenAutenticacion(null);
        $user->setLatitud(null);
        $user->setLongitud(null);
        $user->setFechaActualizacionUbicacion(null);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'salasPrivadasEliminadas' => $salasEliminadas
        ]);
    }

    /**
     * Register endpoint - Crea un nuevo usuario
     */
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar datos
        if (!isset($data['nombre']) || !isset($data['correo']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'error' => 'Se requieren nombre, correo y password'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar que el correo no exista
        $existingUser = $this->userRepository->findOneBy(['correo' => $data['correo']]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'error' => 'el correo ya está registrado'
            ], Response::HTTP_CONFLICT);
        }

        // Crear nuevo usuario
        $user = new User();
        $user->setNombre($data['nombre']);
        $user->setCorreo($data['correo']);

        // Hashear contraseña
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Generar token y marcar como conectado automáticamente
        $token = bin2hex(random_bytes(32));
        $user->setEstado(true); // Usuario conectado al registrarse
        $user->setTokenAutenticacion($token);
        $user->setFechaInicioSesion(new \DateTime());

        // Guardar coordenadas si se proporcionan
        if (isset($data['latitud']) && isset($data['longitud'])) {
            $user->setLatitud($data['latitud']);
            $user->setLongitud($data['longitud']);
            $user->setFechaActualizacionUbicacion(new \DateTime());
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'token' => 1,
                'user' => [
                    'nombre' => $user->getNombre(),
                    'correo' => $user->getCorreo()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Perfil endpoint - Devuelve información del usuario autenticado
     * Requiere autenticación mediante token Bearer
     */
    #[Route('/perfil', name: 'api_perfil', methods: ['GET'])]
    public function perfil(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar que el usuario esté activo
        if (!$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario inactivo'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $user->getId(),
                'user' => [
                    'id' => $user->getId(),
                    'nombre' => $user->getNombre(),
                    'correo' => $user->getCorreo(),
                    'estado' => $user->isEstado(),
                    'latitud' => $user->getLatitud(),
                    'longitud' => $user->getLongitud()
                ]
            ]
        ]);
    }

    /**
     * Actualizar ubicación - Actualiza las coordenadas del usuario autenticado
     */
    #[Route('/actualizar', name: 'api_actualizar_ubicacion', methods: ['POST'])]
    public function actualizarUbicacion(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['latitud']) || !isset($data['longitud'])) {
            return $this->json([
                'success' => false,
                'error' => 'Se requieren latitud y longitud'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setLatitud($data['latitud']);
        $user->setLongitud($data['longitud']);
        $user->setFechaActualizacionUbicacion(new \DateTime());

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Ubicación actualizada correctamente',
            'latitud' => $user->getLatitud(),
            'longitud' => $user->getLongitud()
        ]);
    }

    /**
     * Home endpoint - Lista usuarios activos cercanos (máximo 5km)
     */
    #[Route('/home', name: 'api_home', methods: ['GET'])]
    public function home(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener TODOS los usuarios activos (excepto el actual)
        $usuariosActivos = $this->userRepository->createQueryBuilder('u')
            ->where('u.estado = :estado')
            ->andWhere('u.id != :userId')
            ->setParameter('estado', true)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();

        $usuariosCercanos = [];

        foreach ($usuariosActivos as $usuario) {
            // Calcular distancia si ambos tienen coordenadas
            if ($user->getLatitud() && $user->getLongitud() &&
                $usuario->getLatitud() && $usuario->getLongitud()) {

                $distancia = $this->geoLocationService->calcularDistancia(
                    $user->getLatitud(),
                    $user->getLongitud(),
                    $usuario->getLatitud(),
                    $usuario->getLongitud()
                );

                // Si está a menos de 5km, agregarlo a usuarios cercanos
                if ($distancia <= 5.0) {
                    $usuariosCercanos[] = [
                        'id' => $usuario->getId(),
                        'nombre' => $usuario->getNombre(),
                        'correo' => $usuario->getCorreo(),
                        'latitud' => $usuario->getLatitud(),
                        'longitud' => $usuario->getLongitud(),
                        'distancia' => $distancia
                    ];
                }
            }
        }

        // Ordenar por distancia
        usort($usuariosCercanos, fn($a, $b) => $a['distancia'] <=> $b['distancia']);

        return $this->json([
            'success' => true,
            'message' => 'Asi compruebas que vaya',
            'data' => [
                'token' => $user->getId(),
                'usuariosCercanos' => $usuariosCercanos
            ],
            'total' => count($usuariosCercanos)
        ]);
    }

    /**
     * Usuarios endpoint - Lista todos los usuarios activos
     * Requiere autenticación mediante token Bearer
     */
    #[Route('/usuarios', name: 'api_usuarios', methods: ['GET'])]
    public function usuarios(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener TODOS los usuarios activos (excepto el actual)
        $usuariosActivos = $this->userRepository->createQueryBuilder('u')
            ->where('u.estado = :estado')
            ->andWhere('u.id != :userId')
            ->setParameter('estado', true)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();

        $todosUsuarios = [];

        foreach ($usuariosActivos as $usuario) {
            $datos = [
                'id' => $usuario->getId(),
                'nombre' => $usuario->getNombre(),
                'correo' => $usuario->getCorreo(),
                'latitud' => $usuario->getLatitud(),
                'longitud' => $usuario->getLongitud(),
                'distancia' => null
            ];

            // Calcular distancia si ambos tienen coordenadas
            if ($user->getLatitud() && $user->getLongitud() &&
                $usuario->getLatitud() && $usuario->getLongitud()) {
                $datos['distancia'] = $this->geoLocationService->calcularDistancia(
                    $user->getLatitud(),
                    $user->getLongitud(),
                    $usuario->getLatitud(),
                    $usuario->getLongitud()
                );
            }

            $todosUsuarios[] = $datos;
        }

        // Ordenar por distancia (los que tienen distancia primero)
        usort($todosUsuarios, function($a, $b) {
            if ($a['distancia'] === null) return 1;
            if ($b['distancia'] === null) return -1;
            return $a['distancia'] <=> $b['distancia'];
        });

        return $this->json([
            'success' => true,
            'data' => $todosUsuarios,
            'total' => count($todosUsuarios)
        ]);
    }

    /**
     * Chat General - Obtener mensajes desde que el usuario inició sesión
     * GET /api/general
     */
    #[Route('/general', name: 'api_chat_general_get', methods: ['GET'])]
    public function chatGeneralGet(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener o crear la sala general
        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General']);

        if (!$salaGeneral) {
            // Crear la sala general si no existe
            $salaGeneral = new Sala();
            $salaGeneral->setNombre('General');
            $salaGeneral->setActiva(true);
            $salaGeneral->setFechaCreacion(new \DateTime());
            $this->entityManager->persist($salaGeneral);
            $this->entityManager->flush();
        }

        // Obtener la fecha de inicio de sesión del usuario
        $fechaInicioSesion = $user->getFechaInicioSesion();

        if (!$fechaInicioSesion) {
            // Si no tiene fecha de inicio de sesión, usar la fecha actual
            $fechaInicioSesion = new \DateTime();
            $user->setFechaInicioSesion($fechaInicioSesion);
            $this->entityManager->flush();
        }

        // Obtener mensajes de la sala general desde que el usuario inició sesión
        // Solo de usuarios activos
        $mensajes = $this->mensageRepository->createQueryBuilder('m')
            ->innerJoin('m.autor', 'a')
            ->where('m.sala = :sala')
            ->andWhere('m.fechaCreacion >= :fechaInicio')
            ->andWhere('a.estado = :activo')
            ->setParameter('sala', $salaGeneral)
            ->setParameter('fechaInicio', $fechaInicioSesion)
            ->setParameter('activo', true)
            ->orderBy('m.fechaCreacion', 'ASC')
            ->getQuery()
            ->getResult();

        // Formatear mensajes
        $mensajesFormateados = [];
        foreach ($mensajes as $mensaje) {
            $mensajesFormateados[] = [
                'id' => $mensaje->getId(),
                'contenido' => $mensaje->getContenido(),
                'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                'autor' => [
                    'id' => $mensaje->getAutor()->getId(),
                    'nombre' => $mensaje->getAutor()->getNombre()
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $user->getId(),
                'geolocalizacion' => [
                    'latitud' => $user->getLatitud(),
                    'longitud' => $user->getLongitud()
                ],
                'mensajes' => $mensajesFormateados,
                'total' => count($mensajesFormateados)
            ]
        ]);
    }

    /**
     * Chat General - Enviar mensaje
     * POST /api/general
     */
    #[Route('/general', name: 'api_chat_general_post', methods: ['POST'])]
    public function chatGeneralPost(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['contenido']) || trim($data['contenido']) === '') {
            return $this->json([
                'success' => false,
                'error' => 'El contenido del mensaje es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Obtener o crear la sala general
        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General']);

        if (!$salaGeneral) {
            // Crear la sala general si no existe
            $salaGeneral = new Sala();
            $salaGeneral->setNombre('General');
            $salaGeneral->setActiva(true);
            $salaGeneral->setFechaCreacion(new \DateTime());
            $this->entityManager->persist($salaGeneral);
            $this->entityManager->flush();
        }

        // Crear el mensaje
        $mensaje = new Mensage();
        $mensaje->setContenido(trim($data['contenido']));
        $mensaje->setFechaCreacion(new \DateTime());
        $mensaje->setAutor($user);
        $mensaje->setSala($salaGeneral);
        $mensaje->setLeidoPor([]); // Inicialmente nadie lo ha leído

        $this->entityManager->persist($mensaje);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'correcto',
            'data' => [
                'mensaje' => [
                    'id' => $mensaje->getId(),
                    'mensajes' => 'mostrar mensajes',
                    'contenido' => $mensaje->getContenido(),
                    'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                    'autor' => [
                        'token' => $user->getId(),
                        'nombre' => $user->getNombre()
                    ]
                ],
                'Listado provisionales' => [
                    'usuarios proximos' => [
                        'token' => $user->getId(),
                        'nombre' => $user->getNombre(),
                        'imagen' => 'si hay avatar',
                        'distancia' => 'especificar la distancia que tiene que haber'
                    ],
                    'invitaciones' => [
                        'token' => $user->getId(),
                        'nombre' => $user->getNombre(),
                        'message' => ''
                    ],
                    'listado usuario chat' => [
                        'token' => $user->getId(),
                        'nombre' => $user->getNombre(),
                        'imagen' => 'si hay avatar'
                    ]
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Cambiar chat de sala privada
     * POST /api/privado/cambiarchat
     */
    #[Route('/privado/cambiarchat', name: 'api_cambiar_chat_privado', methods: ['POST'])]
    public function cambiarChatPrivado(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['sala_id'])) {
            return $this->json([
                'success' => false,
                'error' => 'El ID de la sala es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($data['sala_id']);

        if (!$sala) {
            return $this->json([
                'success' => false,
                'error' => 'La sala especificada no existe'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'success' => false,
                'error' => 'No tienes acceso a esta sala'
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'success' => true,
            'message' => 'Sala cambiada correctamente',
            'data' => [
                'sala' => [
                    'id' => $sala->getId(),
                    'nombre' => $sala->getNombre()
                ]
            ]
        ]);
    }

    /**
     * Salir de una sala privada
     * POST /api/privado/salir
     */
    #[Route('/privado/salir', name: 'api_salir_sala_privada', methods: ['POST'])]
    public function salirSalaPrivada(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['sala_id'])) {
            return $this->json([
                'success' => false,
                'error' => 'El ID de la sala es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($data['sala_id']);

        if (!$sala) {
            return $this->json([
                'success' => false,
                'error' => 'La sala especificada no existe'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'success' => false,
                'error' => 'No puedes salir de una sala en la que no participas'
            ], Response::HTTP_FORBIDDEN);
        }

        // Eliminar mensajes del usuario en esta sala
        $mensajes = $this->mensageRepository->createQueryBuilder('m')
            ->where('m.sala = :sala')
            ->andWhere('m.autor = :usuario')
            ->setParameter('sala', $sala)
            ->setParameter('usuario', $user)
            ->getQuery()
            ->getResult();

        foreach ($mensajes as $mensaje) {
            $this->entityManager->remove($mensaje);
        }

        // Verificar si hay otros usuarios activos
        $otrosUsuariosActivos = false;
        foreach ($sala->getUsuarios() as $participante) {
            if ($participante->getId() !== $user->getId() && $participante->isEstado()) {
                $otrosUsuariosActivos = true;
                break;
            }
        }

        // Si no hay otros usuarios activos, eliminar la sala
        if (!$otrosUsuariosActivos) {
            $mensajesRestantes = $this->mensageRepository->findBy(['sala' => $sala]);
            foreach ($mensajesRestantes as $mensaje) {
                $this->entityManager->remove($mensaje);
            }
            $this->entityManager->remove($sala);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Has salido de la sala correctamente',
            'data' => [
                'sala' => [
                    'id' => $sala->getId(),
                    'nombre' => $sala->getNombre()
                ]
            ]
        ]);
    }

    /**
     * Lista de salas privadas
     * GET /api/privado
     */
    #[Route('/privado', name: 'api_listar_privado', methods: ['GET'])]
    public function listarSalasPrivadas(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener salas privadas del usuario
        $salas = $this->salaRepository->createQueryBuilder('s')
            ->innerJoin('s.usuarios', 'u')
            ->where('u.id = :userId')
            ->andWhere('s.tipo = :tipo')
            ->andWhere('s.activa = :activa')
            ->setParameter('userId', $user->getId())
            ->setParameter('tipo', 'privada')
            ->setParameter('activa', true)
            ->orderBy('s.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();

        $salasFormateadas = [];
        foreach ($salas as $sala) {
            $usuarios = [];
            foreach ($sala->getUsuarios() as $participante) {
                $usuarios[] = [
                    'id' => $participante->getId(),
                    'nombre' => $participante->getNombre()
                ];
            }

            $salasFormateadas[] = [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre(),
                'usuarios' => $usuarios
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $salasFormateadas,
            'total' => count($salasFormateadas)
        ]);
    }

    /**
     * Invitar usuarios a sala privada
     * POST /api/invitar
     */
    #[Route('/invitar', name: 'api_invitar_usuarios', methods: ['POST'])]
    public function invitarUsuarios(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['sala_id']) || !isset($data['usuario_id'])) {
            return $this->json([
                'success' => false,
                'error' => 'Se requieren sala_id y usuario_id'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($data['sala_id']);
        if (!$sala) {
            return $this->json([
                'success' => false,
                'error' => 'La sala especificada no existe'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'success' => false,
                'error' => 'No tienes acceso a esta sala'
            ], Response::HTTP_FORBIDDEN);
        }

        $usuarioIds = is_array($data['usuario_id']) ? $data['usuario_id'] : [$data['usuario_id']];
        $usuariosInvitados = [];

        foreach ($usuarioIds as $usuarioId) {
            $usuario = $this->userRepository->find($usuarioId);
            if (!$usuario) {
                return $this->json([
                    'success' => false,
                    'error' => 'uno o varios usuarios no existen'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$sala->getUsuarios()->contains($usuario)) {
                $sala->addUsuario($usuario);
                $usuariosInvitados[] = [
                    'id' => $usuario->getId(),
                    'nombre' => $usuario->getNombre()
                ];
            }
        }

        $this->entityManager->flush();

        // Formatear todos los usuarios de la sala
        $todosUsuarios = [];
        foreach ($sala->getUsuarios() as $participante) {
            $todosUsuarios[] = [
                'id' => $participante->getId(),
                'nombre' => $participante->getNombre()
            ];
        }

        return $this->json([
            'success' => true,
            'message' => 'Usuarios invitados correctamente',
            'sala' => [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre(),
                'usuarios' => $todosUsuarios
            ]
        ]);
    }

    /**
     * Gestión de mensajes - Endpoint unificado
     * POST/GET/DELETE /api/mensaje
     */
    #[Route('/mensaje', name: 'api_mensaje_post', methods: ['POST'])]
    public function crearMensaje(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['contenido']) || trim($data['contenido']) === '') {
            return $this->json([
                'success' => false,
                'error' => 'El contenido del mensaje es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Determinar sala (si no se especifica sala_id, usar sala general)
        $sala = null;
        if (isset($data['sala_id'])) {
            $sala = $this->salaRepository->find($data['sala_id']);
            if (!$sala) {
                return $this->json([
                    'success' => false,
                    'error' => 'La sala especificada no existe'
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            // Usar sala general
            $sala = $this->salaRepository->findOneBy(['nombre' => 'General']);
            if (!$sala) {
                $sala = new Sala();
                $sala->setNombre('General');
                $sala->setActiva(true);
                $sala->setFechaCreacion(new \DateTime());
                $this->entityManager->persist($sala);
                $this->entityManager->flush();
            }
        }

        // Crear el mensaje
        $mensaje = new Mensage();
        $mensaje->setContenido(trim($data['contenido']));
        $mensaje->setFechaCreacion(new \DateTime());
        $mensaje->setAutor($user);
        $mensaje->setSala($sala);
        $mensaje->setLeidoPor([]);

        $this->entityManager->persist($mensaje);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'mensaje' => [
                'id' => $mensaje->getId(),
                'contenido' => $mensaje->getContenido(),
                'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                'autor' => [
                    'id' => $user->getId(),
                    'nombre' => $user->getNombre()
                ],
                'sala_id' => $sala->getId()
            ]
        ]);
    }

    #[Route('/mensaje', name: 'api_mensaje_get', methods: ['GET'])]
    public function obtenerMensajes(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $salaId = $request->query->get('sala_id');
        $sala = null;

        if ($salaId) {
            $sala = $this->salaRepository->find($salaId);
            if (!$sala) {
                return $this->json([
                    'success' => false,
                    'error' => 'La sala especificada no existe'
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            // Usar sala general por defecto
            $sala = $this->salaRepository->findOneBy(['nombre' => 'General']);
            if (!$sala) {
                return $this->json([
                    'success' => true,
                    'mensajes' => [],
                    'total' => 0
                ]);
            }
        }

        // Obtener mensajes de la sala
        $mensajes = $this->mensageRepository->createQueryBuilder('m')
            ->where('m.sala = :sala')
            ->setParameter('sala', $sala)
            ->orderBy('m.fechaCreacion', 'ASC')
            ->getQuery()
            ->getResult();

        $mensajesFormateados = [];
        foreach ($mensajes as $mensaje) {
            $mensajesFormateados[] = [
                'id' => $mensaje->getId(),
                'contenido' => $mensaje->getContenido(),
                'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                'autor' => [
                    'id' => $mensaje->getAutor()->getId(),
                    'nombre' => $mensaje->getAutor()->getNombre()
                ],
                'sala_id' => $sala->getId()
            ];
        }

        return $this->json([
            'success' => true,
            'mensajes' => $mensajesFormateados,
            'total' => count($mensajesFormateados)
        ]);
    }

    /**
     * Gestión de mensajes - Eliminar mensaje (ya existe pero actualizar respuesta)
     * DELETE /api/mensaje
     */
    #[Route('/mensaje', name: 'api_eliminar_mensaje', methods: ['DELETE'])]
    public function eliminarMensaje(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'success' => false,
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['mensaje_id'])) {
            return $this->json([
                'success' => false,
                'error' => 'El ID del mensaje es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el mensaje
        $mensaje = $this->mensageRepository->find($data['mensaje_id']);

        if (!$mensaje) {
            return $this->json([
                'success' => false,
                'error' => 'El mensaje no existe'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el mensaje sea del usuario
        if ($mensaje->getAutor()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'error' => 'No puedes eliminar un mensaje que no es tuyo'
            ], Response::HTTP_FORBIDDEN);
        }

        // Eliminar el mensaje
        $this->entityManager->remove($mensaje);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Mensaje eliminado correctamente'
        ]);
    }
}
