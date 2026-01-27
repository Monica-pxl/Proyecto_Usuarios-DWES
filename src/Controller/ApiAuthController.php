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
            'user' => [
                'id' => $user->getId(),
                'correo' => $user->getCorreo(),
                'nombre' => $user->getNombre()
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

        // Eliminar todas las salas privadas y sus mensajes
        $salasEliminadas = 0;
        foreach ($salasPrivadas as $sala) {
            // Eliminar mensajes de la sala
            $mensajes = $this->mensageRepository->findBy(['sala' => $sala]);
            foreach ($mensajes as $mensaje) {
                $this->entityManager->remove($mensaje);
            }
            
            // Eliminar la sala
            $this->entityManager->remove($sala);
            $salasEliminadas++;
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
                'error' => 'El correo ya está registrado'
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
            'message' => 'Usuario registrado e iniciado sesión exitosamente',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'nombre' => $user->getNombre(),
                'correo' => $user->getCorreo()
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
            'user' => [
                'id' => $user->getId(),
                'nombre' => $user->getNombre(),
                'correo' => $user->getCorreo(),
                'estado' => $user->isEstado(),
                'latitud' => $user->getLatitud(),
                'longitud' => $user->getLongitud()
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
            'usuariosCercanos' => $usuariosCercanos,
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
            'usuarios' => $todosUsuarios,
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
            'mensajes' => $mensajesFormateados,
            'total' => count($mensajesFormateados)
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
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['contenido']) || trim($data['contenido']) === '') {
            return $this->json([
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
            'mensaje' => [
                'id' => $mensaje->getId(),
                'contenido' => $mensaje->getContenido(),
                'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                'autor' => [
                    'id' => $user->getId(),
                    'nombre' => $user->getNombre()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Crear sala privada entre dos usuarios
     * POST /api/sala-privada/crear
     */
    #[Route('/sala-privada/crear', name: 'api_crear_sala_privada', methods: ['POST'])]
    public function crearSalaPrivada(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['usuarioId'])) {
            return $this->json([
                'error' => 'Se requiere el ID del usuario destinatario'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario destinatario
        $otroUsuario = $this->userRepository->find($data['usuarioId']);

        if (!$otroUsuario) {
            return $this->json([
                'error' => 'Usuario destinatario no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$otroUsuario->isEstado()) {
            return $this->json([
                'error' => 'El usuario destinatario no está activo'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si ya existe una sala privada entre estos dos usuarios (activa o pendiente)
        $salaExistente = $this->salaRepository->createQueryBuilder('s')
            ->innerJoin('s.usuarios', 'u1')
            ->innerJoin('s.usuarios', 'u2')
            ->where('s.tipo = :tipo')
            ->andWhere('u1.id = :user1Id')
            ->andWhere('u2.id = :user2Id')
            ->setParameter('tipo', 'privada')
            ->setParameter('user1Id', $user->getId())
            ->setParameter('user2Id', $otroUsuario->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if ($salaExistente) {
            // Si ya existe una sala
            if ($salaExistente->isActiva()) {
                return $this->json([
                    'success' => true,
                    'sala' => [
                        'id' => $salaExistente->getId(),
                        'nombre' => $salaExistente->getNombre(),
                        'tipo' => $salaExistente->getTipo(),
                        'activa' => true,
                        'participantes' => array_map(function($u) {
                            return [
                                'id' => $u->getId(),
                                'nombre' => $u->getNombre()
                            ];
                        }, $salaExistente->getUsuarios()->toArray())
                    ],
                    'message' => 'Ya existe una sala activa con este usuario'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'Ya existe una invitación pendiente con este usuario'
                ], Response::HTTP_CONFLICT);
            }
        }

        // Crear nueva sala privada PENDIENTE (invitación)
        $sala = new Sala();
        $sala->setNombre('Chat privado: ' . $user->getNombre() . ' & ' . $otroUsuario->getNombre());
        $sala->setTipo('privada');
        $sala->setActiva(false); // Pendiente de aceptación
        $sala->setCreador($user); // Quien envía la invitación
        $sala->setFechaCreacion(new \DateTime());
        
        // Agregar ambos usuarios a la sala
        $sala->addUsuario($user);
        $sala->addUsuario($otroUsuario);

        $this->entityManager->persist($sala);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Invitación enviada a ' . $otroUsuario->getNombre(),
            'invitacion' => [
                'id' => $sala->getId(),
                'destinatario' => [
                    'id' => $otroUsuario->getId(),
                    'nombre' => $otroUsuario->getNombre()
                ],
                'estado' => 'pendiente'
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Obtener invitaciones pendientes recibidas
     * GET /api/invitaciones-pendientes
     */
    #[Route('/invitaciones-pendientes', name: 'api_invitaciones_pendientes', methods: ['GET'])]
    public function obtenerInvitacionesPendientes(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener salas donde el usuario participa, están pendientes y NO es el creador
        $invitaciones = $this->salaRepository->createQueryBuilder('s')
            ->innerJoin('s.usuarios', 'u')
            ->where('s.tipo = :tipo')
            ->andWhere('s.activa = :activa')
            ->andWhere('u.id = :userId')
            ->andWhere('s.creador != :userId')
            ->setParameter('tipo', 'privada')
            ->setParameter('activa', false)
            ->setParameter('userId', $user->getId())
            ->orderBy('s.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();

        $invitacionesFormateadas = [];
        foreach ($invitaciones as $sala) {
            $creador = $sala->getCreador();
            $invitacionesFormateadas[] = [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre(),
                'fechaCreacion' => $sala->getFechaCreacion()->format('Y-m-d H:i:s'),
                'remitente' => [
                    'id' => $creador->getId(),
                    'nombre' => $creador->getNombre(),
                    'correo' => $creador->getCorreo()
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'invitaciones' => $invitacionesFormateadas,
            'total' => count($invitacionesFormateadas)
        ]);
    }

    /**
     * Aceptar invitación a sala privada
     * POST /api/invitacion/{salaId}/aceptar
     */
    #[Route('/invitacion/{salaId}/aceptar', name: 'api_aceptar_invitacion', methods: ['POST'])]
    public function aceptarInvitacion(int $salaId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($salaId);

        if (!$sala) {
            return $this->json([
                'error' => 'Invitación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que es una sala privada pendiente
        if ($sala->getTipo() !== 'privada' || $sala->isActiva()) {
            return $this->json([
                'error' => 'Esta invitación no es válida'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar que el usuario es participante pero NO el creador
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'error' => 'No tienes acceso a esta invitación'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($sala->getCreador() && $sala->getCreador()->getId() === $user->getId()) {
            return $this->json([
                'error' => 'No puedes aceptar tu propia invitación'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Activar la sala
        $sala->setActiva(true);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Invitación aceptada',
            'sala' => [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre(),
                'tipo' => $sala->getTipo(),
                'participantes' => array_map(function($u) {
                    return [
                        'id' => $u->getId(),
                        'nombre' => $u->getNombre()
                    ];
                }, $sala->getUsuarios()->toArray())
            ]
        ]);
    }

    /**
     * Rechazar invitación a sala privada
     * POST /api/invitacion/{salaId}/rechazar
     */
    #[Route('/invitacion/{salaId}/rechazar', name: 'api_rechazar_invitacion', methods: ['POST'])]
    public function rechazarInvitacion(int $salaId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($salaId);

        if (!$sala) {
            return $this->json([
                'error' => 'Invitación no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que es una sala privada pendiente
        if ($sala->getTipo() !== 'privada' || $sala->isActiva()) {
            return $this->json([
                'error' => 'Esta invitación no es válida'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar que el usuario es participante pero NO el creador
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'error' => 'No tienes acceso a esta invitación'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($sala->getCreador() && $sala->getCreador()->getId() === $user->getId()) {
            return $this->json([
                'error' => 'No puedes rechazar tu propia invitación'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Eliminar la sala
        $this->entityManager->remove($sala);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Invitación rechazada'
        ]);
    }

    /**
     * Obtener salas privadas del usuario autenticado (solo activas)
     * GET /api/salas-privadas
     */
    #[Route('/salas-privadas', name: 'api_salas_privadas', methods: ['GET'])]
    public function obtenerSalasPrivadas(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener todas las salas privadas ACTIVAS donde participa el usuario
        $salas = $this->salaRepository->createQueryBuilder('s')
            ->innerJoin('s.usuarios', 'u')
            ->where('s.tipo = :tipo')
            ->andWhere('u.id = :userId')
            ->andWhere('s.activa = :activa')
            ->setParameter('tipo', 'privada')
            ->setParameter('userId', $user->getId())
            ->setParameter('activa', true)
            ->orderBy('s.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();

        $salasFormateadas = [];
        foreach ($salas as $sala) {
            // Obtener el otro usuario (no el actual)
            $participantes = [];
            $otroUsuario = null;
            foreach ($sala->getUsuarios() as $participante) {
                if ($participante->getId() !== $user->getId()) {
                    $otroUsuario = $participante;
                }
                $participantes[] = [
                    'id' => $participante->getId(),
                    'nombre' => $participante->getNombre()
                ];
            }

            $salasFormateadas[] = [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre(),
                'tipo' => $sala->getTipo(),
                'participantes' => $participantes,
                'otroUsuario' => $otroUsuario ? [
                    'id' => $otroUsuario->getId(),
                    'nombre' => $otroUsuario->getNombre()
                ] : null
            ];
        }

        return $this->json([
            'success' => true,
            'salas' => $salasFormateadas,
            'total' => count($salasFormateadas)
        ]);
    }

    /**
     * Obtener mensajes de una sala privada
     * GET /api/sala-privada/{salaId}/mensajes
     */
    #[Route('/sala-privada/{salaId}/mensajes', name: 'api_mensajes_sala_privada', methods: ['GET'])]
    public function obtenerMensajesSalaPrivada(int $salaId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($salaId);

        if (!$sala) {
            return $this->json([
                'error' => 'Sala no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'error' => 'No tienes acceso a esta sala'
            ], Response::HTTP_FORBIDDEN);
        }

        // Obtener mensajes de la sala
        $mensajes = $this->mensageRepository->createQueryBuilder('m')
            ->where('m.sala = :sala')
            ->setParameter('sala', $sala)
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
            'mensajes' => $mensajesFormateados,
            'sala' => [
                'id' => $sala->getId(),
                'nombre' => $sala->getNombre()
            ]
        ]);
    }

    /**
     * Enviar mensaje a una sala privada
     * POST /api/sala-privada/{salaId}/mensaje
     */
    #[Route('/sala-privada/{salaId}/mensaje', name: 'api_enviar_mensaje_sala_privada', methods: ['POST'])]
    public function enviarMensajeSalaPrivada(int $salaId, Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($salaId);

        if (!$sala) {
            return $this->json([
                'error' => 'Sala no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'error' => 'No tienes acceso a esta sala'
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['contenido']) || trim($data['contenido']) === '') {
            return $this->json([
                'error' => 'El contenido del mensaje es requerido'
            ], Response::HTTP_BAD_REQUEST);
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
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Eliminar sala privada
     * DELETE /api/sala-privada/{salaId}
     */
    #[Route('/sala-privada/{salaId}', name: 'api_eliminar_sala_privada', methods: ['DELETE'])]
    public function eliminarSalaPrivada(int $salaId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Buscar la sala
        $sala = $this->salaRepository->find($salaId);

        if (!$sala) {
            return $this->json([
                'error' => 'Sala no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }

        // Verificar que sea una sala privada
        if ($sala->getTipo() !== 'privada') {
            return $this->json([
                'error' => 'Solo se pueden eliminar salas privadas'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar que el usuario sea participante de la sala
        if (!$sala->getUsuarios()->contains($user)) {
            return $this->json([
                'error' => 'No tienes acceso a esta sala'
            ], Response::HTTP_FORBIDDEN);
        }

        // Eliminar todos los mensajes de la sala primero
        $mensajes = $this->mensageRepository->findBy(['sala' => $sala]);
        foreach ($mensajes as $mensaje) {
            $this->entityManager->remove($mensaje);
        }

        // Eliminar la sala
        $this->entityManager->remove($sala);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Sala eliminada correctamente'
        ]);
    }
}
