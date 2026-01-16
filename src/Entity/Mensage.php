<?php

namespace App\Entity;

use App\Repository\MensageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MensageRepository::class)]
class Mensage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenido = null;

    #[ORM\Column]
    private ?\DateTime $fechaCreacion = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $leidoPor = null;

    // Renombrado a "autor"
    #[ORM\ManyToOne(inversedBy: 'mensajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $autor = null;

    #[ORM\ManyToOne(inversedBy: 'mensajes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sala $sala = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenido(): ?string
    {
        return $this->contenido;
    }

    public function setContenido(string $contenido): static
    {
        $this->contenido = $contenido;
        return $this;
    }

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(\DateTime $fechaCreacion): static
    {
        $this->fechaCreacion = $fechaCreacion;
        return $this;
    }

    public function getLeidoPor(): ?array
    {
        return $this->leidoPor;
    }

    public function setLeidoPor(?array $leidoPor): static
    {
        $this->leidoPor = $leidoPor;
        return $this;
    }

    public function getAutor(): ?User
    {
        return $this->autor;
    }

    public function setAutor(?User $autor): static
    {
        $this->autor = $autor;
        return $this;
    }

    public function getSala(): ?Sala
    {
        return $this->sala;
    }

    public function setSala(?Sala $sala): static
    {
        $this->sala = $sala;
        return $this;
    }
}
