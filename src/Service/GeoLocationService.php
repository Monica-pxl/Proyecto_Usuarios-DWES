<?php

namespace App\Service;

class GeoLocationService
{
    /**
     * Calcula la distancia entre dos puntos usando la fórmula de Haversine
     */
    public function calcularDistancia(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radioTierra = 6371; // Radio de la Tierra en km

        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($radioTierra * $c, 2);
    }

    /**
     * Filtra usuarios cercanos dentro de un radio específico
     */
    public function filtrarUsuariosCercanos(
        array $usuarios,
        float $latitudUsuario,
        float $longitudUsuario,
        float $radioKm = 5.0
    ): array {
        $usuariosCercanos = [];

        foreach ($usuarios as $usuario) {
            if ($usuario->getLatitud() === null || $usuario->getLongitud() === null) {
                continue;
            }

            $distancia = $this->calcularDistancia(
                $latitudUsuario,
                $longitudUsuario,
                $usuario->getLatitud(),
                $usuario->getLongitud()
            );

            if ($distancia <= $radioKm) {
                $usuariosCercanos[] = [
                    'usuario' => $usuario,
                    'distancia' => $distancia
                ];
            }
        }

        usort($usuariosCercanos, fn($a, $b) => $a['distancia'] <=> $b['distancia']);

        return $usuariosCercanos;
    }
}
