<?php
namespace App\Entity;

use App\Repository\SiteRepository;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(type: 'string', length: 120)]
    private string $domain;

    // column name is `key` in DB; use property $siteKey
    #[ORM\Column(name: '`key`', type: 'string', length: 50)]
    private string $siteKey;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $webhook_url = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $webhook_token = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /** @var Collection<int, Form> */
    #[ORM\OneToMany(mappedBy: 'site', targetEntity: Form::class)]
    private Collection $forms;

    public function __construct()
    {
        $this->forms = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $s): self
    {
        $this->slug = $s;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $s): self
    {
        $this->name = $s;
        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $d): self
    {
        $this->domain = $d;
        return $this;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function setSiteKey(string $k): self
    {
        $this->siteKey = $k;
        return $this;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhook_url;
    }

    public function setWebhookUrl(?string $u): self
    {
        $this->webhook_url = $u;
        return $this;
    }

    public function getWebhookToken(): ?string
    {
        return $this->webhook_token;
    }

    public function setWebhookToken(?string $t): self
    {
        $this->webhook_token = $t;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $u): self
    {
        $this->user = $u;
        return $this;
    }

    /** @return Collection<int, Form> */
    public function getForms(): Collection
    {
        return $this->forms;
    }
}
