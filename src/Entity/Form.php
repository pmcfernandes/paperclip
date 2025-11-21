<?php
namespace App\Entity;

use App\Repository\FormRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormRepository::class)]
#[ORM\Table(name: 'forms')]
class Form
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'forms')]
    #[ORM\JoinColumn(name: 'site_id', referencedColumnName: 'id', nullable: false)]
    private Site $site;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $slug = null;
    #[ORM\Column(type: 'string', length: 256)]
    private string $name;

    #[ORM\Column(name: 'emailOnSubmit', type: 'boolean')]
    private bool $emailOnSubmit = true;

    #[ORM\Column(name: 'sendOnSubmit', type: 'string', length: 256, nullable: true)]
    private ?string $sendOnSubmit = null;

    #[ORM\Column(name: 'urlOnOk', type: 'string', length: 1024, nullable: true)]
    private ?string $urlOnOk = null;

    #[ORM\Column(name: 'urlOnError', type: 'string', length: 1024, nullable: true)]
    private ?string $urlOnError = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): self
    {
        $this->site = $site;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isEmailOnSubmit(): bool
    {
        return $this->emailOnSubmit;
    }

    public function setEmailOnSubmit(bool $v): self
    {
        $this->emailOnSubmit = $v;
        return $this;
    }

    public function getSendOnSubmit(): ?string
    {
        return $this->sendOnSubmit;
    }

    public function setSendOnSubmit(?string $s): self
    {
        $this->sendOnSubmit = $s;
        return $this;
    }

    public function getUrlOnOk(): ?string
    {
        return $this->urlOnOk;
    }

    public function setUrlOnOk(?string $s): self
    {
        $this->urlOnOk = $s;
        return $this;
    }

    public function getUrlOnError(): ?string
    {
        return $this->urlOnError;
    }

    public function setUrlOnError(?string $s): self
    {
        $this->urlOnError = $s;
        return $this;
    }
}
