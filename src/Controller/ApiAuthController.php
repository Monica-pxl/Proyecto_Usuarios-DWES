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
                'error' => 'Se requieren correo y password'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar usuario por correo
        $user = $this->userRepository->findOneBy(['correo' => $data['correo']]);

        if (!$user) {
            return $this->json([
                'error' => 'Credenciales inválidas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar contraseña
        if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json([
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
     * Logout endpoint - Invalida el token
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

        // Marcar estado = false y limpiar token y ubicación
        $user->setEstado(false);
        $user->setTokenAutenticacion(null);
        $user->setLatitud(null);
        $user->setLongitud(null);
        $user->setFechaActualizacionUbicacion(null);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
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
                'error' => 'Se requieren nombre, correo y password'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validar que el correo no exista
        $existingUser = $this->userRepository->findOneBy(['correo' => $data['correo']]);
        if ($existingUser) {
            return $this->json([
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

        // Estado inicial = false (no autenticado)
        $user->setEstado(false);
        $user->setTokenAutenticacion(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
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
                'error' => 'No autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar que el usuario esté activo
        if (!$user->isEstado()) {
            return $this->json([
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
    #[Route('/actualizar-ubicacion', name: 'api_actualizar_ubicacion', methods: ['POST'])]
    public function actualizarUbicacion(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['latitud']) || !isset($data['longitud'])) {
            return $this->json(['error' => 'Se requieren latitud y longitud'], Response::HTTP_BAD_REQUEST);
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
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
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
            return $this->json(['error' => 'No autenticado'], Response::HTTP_UNAUTHORIZED);
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
}
