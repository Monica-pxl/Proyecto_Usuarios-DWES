<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nombre;

    #[ORM\Column(length: 255, unique: true)]
    private string $correo;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenAutenticacion = null;

    #[ORM\Column(nullable: true)]
    private ?float $latitud = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitud = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaActualizacionUbicacion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechaInicioSesion = null;

    #[ORM\Column]
    private bool $estado = true;

    // ================== Relaciones ==================

    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'usuario_bloqueado')]
    private Collection $usuariosBloqueados;

    #[ORM\ManyToMany(targetEntity: Sala::class, mappedBy: 'usuarios')]
    private Collection $salas;

    #[ORM\OneToMany(targetEntity: Mensage::class, mappedBy: 'autor')]
    private Collection $mensajes;

    public function __construct()
    {
        $this->usuariosBloqueados = new ArrayCollection();
        $this->salas = new ArrayCollection();
        $this->mensajes = new ArrayCollection();
    }

    // ================== Getters / Setters ==================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): self
    {
        $this->correo = $correo;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getTokenAutenticacion(): ?string
    {
        return $this->tokenAutenticacion;
    }

    public function setTokenAutenticacion(?string $token): self
    {
        $this->tokenAutenticacion = $token;
        return $this;
    }

    public function getLatitud(): ?float
    {
        return $this->latitud;
    }

    public function setLatitud(?float $latitud): self
    {
        $this->latitud = $latitud;
        return $this;
    }

    public function getLongitud(): ?float
    {
        return $this->longitud;
    }

    public function setLongitud(?float $longitud): self
    {
        $this->longitud = $longitud;
        return $this;
    }

    public function getFechaActualizacionUbicacion(): ?\DateTimeInterface
    {
        return $this->fechaActualizacionUbicacion;
    }

    public function setFechaActualizacionUbicacion(?\DateTimeInterface $fecha): self
    {
        $this->fechaActualizacionUbicacion = $fecha;
        return $this;
    }

    public function getFechaInicioSesion(): ?\DateTimeInterface
    {
        return $this->fechaInicioSesion;
    }

    public function setFechaInicioSesion(?\DateTimeInterface $fecha): self
    {
        $this->fechaInicioSesion = $fecha;
        return $this;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    // ================== Usuarios bloqueados ==================

    public function getUsuariosBloqueados(): Collection
    {
        return $this->usuariosBloqueados;
    }

    public function bloquearUsuario(User $usuario): self
    {
        if (!$this->usuariosBloqueados->contains($usuario)) {
            $this->usuariosBloqueados->add($usuario);
        }
        return $this;
    }

    public function desbloquearUsuario(User $usuario): self
    {
        $this->usuariosBloqueados->removeElement($usuario);
        return $this;
    }

    // ================== Salas ==================

    public function getSalas(): Collection
    {
        return $this->salas;
    }

    public function addSala(Sala $sala): static
    {
        if (!$this->salas->contains($sala)) {
            $this->salas->add($sala);
            $sala->addUsuario($this);
        }
        return $this;
    }

    public function removeSala(Sala $sala): static
    {
        if ($this->salas->removeElement($sala)) {
            $sala->removeUsuario($this);
        }
        return $this;
    }

    // ================== Mensajes ==================

    public function getMensajes(): Collection
    {
        return $this->mensajes;
    }

    public function addMensaje(Mensage $mensaje): static
    {
        if (!$this->mensajes->contains($mensaje)) {
            $this->mensajes->add($mensaje);
            $mensaje->setAutor($this);
        }
        return $this;
    }

    public function removeMensaje(Mensage $mensaje): static
    {
        if ($this->mensajes->removeElement($mensaje)) {
            if ($mensaje->getAutor() === $this) {
                $mensaje->setAutor(null);
            }
        }
        return $this;
    }

    // ================== Métodos de Symfony Security ==================

    public function getUserIdentifier(): string
    {
        return $this->correo; // se usará como login
    }

    public function getRoles(): array
    {
        return ['ROLE_USER']; // puedes añadir más roles si quieres
    }

    public function eraseCredentials(): void
    {
        // limpiar datos sensibles si los hubiera
    }
}
