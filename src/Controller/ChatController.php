<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Mensage;
use App\Entity\Sala;
use App\Repository\UserRepository;
use App\Repository\MensageRepository;
use App\Repository\SalaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/chat')]
class ChatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private MensageRepository $mensageRepository,
        private SalaRepository $salaRepository
    ) {}

    /**
     * Obtener información completa del chat (general + privadas + usuarios)
     * GET /api/chat/info
     */
    #[Route('/info', name: 'api_chat_info', methods: ['GET'])]
    public function getChatInfo(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Obtener o crear sala general
        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General', 'tipo' => 'general']);
        
        if (!$salaGeneral) {
            $salaGeneral = new Sala();
            $salaGeneral->setNombre('General');
            $salaGeneral->setTipo('general');
            $salaGeneral->setActiva(true);
            $salaGeneral->setFechaCreacion(new \DateTime());
            $this->entityManager->persist($salaGeneral);
            $this->entityManager->flush();
        }

        // Obtener mensajes del chat general desde que el usuario inició sesión
        $fechaInicioSesion = $user->getFechaInicioSesion() ?? new \DateTime('-1 day');
        
        $mensajesGenerales = $this->mensageRepository->createQueryBuilder('m')
            ->where('m.sala = :sala')
            ->andWhere('m.fechaCreacion >= :fecha')
            ->setParameter('sala', $salaGeneral)
            ->setParameter('fecha', $fechaInicioSesion)
            ->orderBy('m.fechaCreacion', 'ASC')
            ->getQuery()
            ->getResult();

        // Obtener salas privadas del usuario
        $salasPrivadas = $this->salaRepository->createQueryBuilder('s')
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

        // Obtener usuarios disponibles para chat
        $todosUsuarios = $this->userRepository->findBy(['estado' => true]);
        
        $usuariosDisponibles = array_map(function($u) use ($user) {
            if ($u->getId() === $user->getId()) return null;
            
            return [
                'id' => $u->getId(),
                'nombre' => $u->getNombre(),
                'correo' => $u->getCorreo(),
                'enLinea' => $this->isUserOnline($u)
            ];
        }, $todosUsuarios);
        
        $usuariosDisponibles = array_filter($usuariosDisponibles);

        return $this->json([
            'success' => true,
            'usuario' => [
                'id' => $user->getId(),
                'nombre' => $user->getNombre(),
                'correo' => $user->getCorreo()
            ],
            'chatGeneral' => [
                'id' => $salaGeneral->getId(),
                'nombre' => $salaGeneral->getNombre(),
                'cantidadMensajes' => count($mensajesGenerales),
                'mensajes' => array_map(function($m) {
                    return $this->formatMessage($m);
                }, $mensajesGenerales)
            ],
            'salasPrivadas' => array_map(function($s) use ($user) {
                $otroUsuario = null;
                foreach ($s->getUsuarios() as $u) {
                    if ($u->getId() !== $user->getId()) {
                        $otroUsuario = $u;
                        break;
                    }
                }

                $ultimoMensaje = $this->mensageRepository->findOneBy(
                    ['sala' => $s],
                    ['fechaCreacion' => 'DESC']
                );

                return [
                    'id' => $s->getId(),
                    'nombre' => $s->getNombre(),
                    'tipo' => $s->getTipo(),
                    'activa' => $s->isActiva(),
                    'otroUsuario' => $otroUsuario ? [
                        'id' => $otroUsuario->getId(),
                        'nombre' => $otroUsuario->getNombre(),
                        'enLinea' => $this->isUserOnline($otroUsuario)
                    ] : null,
                    'ultimoMensaje' => $ultimoMensaje ? [
                        'contenido' => $ultimoMensaje->getContenido(),
                        'fecha' => $ultimoMensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
                        'autor' => $ultimoMensaje->getAutor()->getNombre()
                    ] : null
                ];
            }, $salasPrivadas),
            'usuariosDisponibles' => array_values($usuariosDisponibles),
            'estadisticas' => [
                'totalUsuarios' => count($todosUsuarios),
                'usuariosEnLinea' => count(array_filter($todosUsuarios, fn($u) => $this->isUserOnline($u))),
                'salasPrivadasActivas' => count($salasPrivadas),
                'mensajesGeneralHoy' => count($mensajesGenerales)
            ]
        ]);
    }

    /**
     * Obtener solo mensajes del chat general
     * GET /api/chat/general/mensajes
     */
    #[Route('/general/mensajes', name: 'api_chat_general_mensajes', methods: ['GET'])]
    public function getGeneralMessages(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General', 'tipo' => 'general']);
        
        if (!$salaGeneral) {
            return $this->json([
                'success' => true,
                'mensajes' => []
            ]);
        }

        $fechaInicioSesion = $user->getFechaInicioSesion() ?? new \DateTime('-1 day');
        
        $mensajes = $this->mensageRepository->createQueryBuilder('m')
            ->where('m.sala = :sala')
            ->andWhere('m.fechaCreacion >= :fecha')
            ->setParameter('sala', $salaGeneral)
            ->setParameter('fecha', $fechaInicioSesion)
            ->orderBy('m.fechaCreacion', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'success' => true,
            'sala' => [
                'id' => $salaGeneral->getId(),
                'nombre' => $salaGeneral->getNombre()
            ],
            'mensajes' => array_map(function($m) {
                return $this->formatMessage($m);
            }, $mensajes),
            'total' => count($mensajes)
        ]);
    }

    /**
     * Enviar mensaje al chat general
     * POST /api/chat/general/mensaje
     */
    #[Route('/general/mensaje', name: 'api_chat_general_enviar', methods: ['POST'])]
    public function sendGeneralMessage(Request $request): JsonResponse
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

        // Obtener o crear sala general
        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General', 'tipo' => 'general']);
        
        if (!$salaGeneral) {
            $salaGeneral = new Sala();
            $salaGeneral->setNombre('General');
            $salaGeneral->setTipo('general');
            $salaGeneral->setActiva(true);
            $salaGeneral->setFechaCreacion(new \DateTime());
            $this->entityManager->persist($salaGeneral);
        }

        // Crear mensaje
        $mensaje = new Mensage();
        $mensaje->setContenido(trim($data['contenido']));
        $mensaje->setFechaCreacion(new \DateTime());
        $mensaje->setAutor($user);
        $mensaje->setSala($salaGeneral);
        $mensaje->setLeidoPor([]);

        $this->entityManager->persist($mensaje);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'mensaje' => $this->formatMessage($mensaje)
        ], Response::HTTP_CREATED);
    }

    /**
     * Obtener lista de usuarios en línea
     * GET /api/chat/usuarios-online
     */
    #[Route('/usuarios-online', name: 'api_chat_usuarios_online', methods: ['GET'])]
    public function getOnlineUsers(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $usuarios = $this->userRepository->findBy(['estado' => true]);
        
        $usuariosOnline = array_map(function($u) {
            $enLinea = $this->isUserOnline($u);
            
            return [
                'id' => $u->getId(),
                'nombre' => $u->getNombre(),
                'correo' => $u->getCorreo(),
                'enLinea' => $enLinea,
                'ultimaActividad' => $u->getFechaInicioSesion() ? 
                    $u->getFechaInicioSesion()->format('Y-m-d H:i:s') : null
            ];
        }, $usuarios);

        return $this->json([
            'success' => true,
            'usuarios' => array_values($usuariosOnline),
            'total' => count($usuariosOnline),
            'enLinea' => count(array_filter($usuariosOnline, fn($u) => $u['enLinea']))
        ]);
    }

    /**
     * Búsqueda de mensajes
     * GET /api/chat/buscar?q=texto
     */
    #[Route('/buscar', name: 'api_chat_buscar', methods: ['GET'])]
    public function searchMessages(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->isEstado()) {
            return $this->json([
                'error' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $query = $request->query->get('q', '');

        if (strlen($query) < 3) {
            return $this->json([
                'error' => 'La búsqueda debe tener al menos 3 caracteres'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Buscar en mensajes del chat general
        $salaGeneral = $this->salaRepository->findOneBy(['nombre' => 'General', 'tipo' => 'general']);
        
        $mensajes = [];
        if ($salaGeneral) {
            $mensajes = $this->mensageRepository->createQueryBuilder('m')
                ->where('m.sala = :sala')
                ->andWhere('m.contenido LIKE :query')
                ->setParameter('sala', $salaGeneral)
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('m.fechaCreacion', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();
        }

        return $this->json([
            'success' => true,
            'query' => $query,
            'resultados' => array_map(function($m) {
                return $this->formatMessage($m);
            }, $mensajes),
            'total' => count($mensajes)
        ]);
    }

    // Métodos auxiliares privados

    private function formatMessage(Mensage $mensaje): array
    {
        return [
            'id' => $mensaje->getId(),
            'contenido' => $mensaje->getContenido(),
            'fechaCreacion' => $mensaje->getFechaCreacion()->format('Y-m-d H:i:s'),
            'autor' => [
                'id' => $mensaje->getAutor()->getId(),
                'nombre' => $mensaje->getAutor()->getNombre(),
                'correo' => $mensaje->getAutor()->getCorreo()
            ],
            'sala' => [
                'id' => $mensaje->getSala()->getId(),
                'nombre' => $mensaje->getSala()->getNombre(),
                'tipo' => $mensaje->getSala()->getTipo()
            ]
        ];
    }

    private function isUserOnline(User $user): bool
    {
        $fechaInicioSesion = $user->getFechaInicioSesion();
        
        if (!$fechaInicioSesion) {
            return false;
        }

        // Considerar online si inició sesión hace menos de 5 minutos
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $fechaInicioSesion->getTimestamp();
        
        return $diff < 300; // 5 minutos = 300 segundos
    }
}
