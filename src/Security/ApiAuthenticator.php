<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Determina si este autenticador debe procesar la petición
     * Solo procesa peticiones con cabecera Authorization Bearer
     */
    public function supports(Request $request): ?bool
    {
        // No procesar rutas públicas (login y register)
        $path = $request->getPathInfo();
        if (in_array($path, ['/api/login', '/api/register'])) {
            return false;
        }

        // Solo procesa si hay un token Bearer en la cabecera Authorization
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    /**
     * Autentica al usuario basándose en el token Bearer
     */
    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');

        // Extraer el token (quitar "Bearer " del inicio)
        $token = substr($authHeader, 7);

        if (empty($token)) {
            throw new CustomUserMessageAuthenticationException('Token no proporcionado');
        }

        // Buscar usuario por token
        $user = $this->userRepository->findOneBy(['tokenAutenticacion' => $token]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Token inválido');
        }

        // Verificar que el usuario esté activo (estado = true)
        if (!$user->isEstado()) {
            throw new CustomUserMessageAuthenticationException('Usuario inactivo');
        }

        // Crear un passport autenticado con el usuario encontrado
        return new SelfValidatingPassport(
            new UserBadge($user->getCorreo(), function() use ($user) {
                return $user;
            })
        );
    }

    /**
     * Llamado cuando la autenticación es exitosa
     * Para APIs, retornamos null para que continúe el request normalmente
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // No hacer nada, dejar que el controller procese la petición
        return null;
    }

    /**
     * Llamado cuando la autenticación falla
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }
}
