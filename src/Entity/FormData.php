<?php
namespace App\Entity;

use App\Repository\FormDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormDataRepository::class)]
#[ORM\Table(name: 'form_data')]
class FormData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Form::class)]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', nullable: false)]
    private Form $form;

    #[ORM\Column(name: 'submit_id', type: 'integer', nullable: false)]
    private ?int $submitId = null;

    #[ORM\Column(type: 'string', length: 256)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column(name: '`when`', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $when = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function setForm(Form $form): self
    {
        $this->form = $form;
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $v): self
    {
        $this->value = $v;
        return $this;
    }

    public function getWhen(): ?\DateTimeInterface
    {
        return $this->when;
    }

    public function setWhen(?\DateTimeInterface $t): self
    {
        $this->when = $t;
        return $this;
    }

    public function getSubmitId(): ?int
    {
        return $this->submitId;
    }

    public function setSubmitId(?int $id): self
    {
        $this->submitId = $id;
        return $this;
    }
}
