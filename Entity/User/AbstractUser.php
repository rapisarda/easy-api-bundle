<?php


namespace EasyApiBundle\Entity\User;


use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as FosUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use \DateTime;

/**
 * @ORM\MappedSuperclass
 * @UniqueEntity(fields="username", message=EasyApiBundle\Util\ApiProblem::USER_USERNAME_ALREADY_EXISTS)
 * @UniqueEntity(fields="email", message=EasyApiBundle\Util\ApiProblem::USER_EMAIL_ALREADY_EXISTS)
 */
abstract class AbstractUser extends FosUser implements EncoderAwareInterface
{
    const ANONYMOUS_PREFIX = 'anonymous_';
    const ROLE_BASIC_USER = 'ROLE_BASIC_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $anonymous = false;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $encoder = '1';

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param FOSUser $fosUser
     * @return AbstractUser
     */
    public function updateFromFosUser(FOSUser $fosUser)
    {
        $this->setId($fosUser->getId());
        $this->setUsername($fosUser->getUsername());
        $this->setUsernameCanonical($fosUser->getUsernameCanonical());
        $this->setPassword($fosUser->getPassword());
        $this->setPlainPassword($fosUser->getPlainPassword());
        $this->setEmail($fosUser->getEmail());
        $this->setEmailCanonical($fosUser->getEmailCanonical());
        $this->setRoles($fosUser->getRoles());
        $this->setSalt($fosUser->getSalt());
        $this->setLastLogin($fosUser->getLastLogin());

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
//        if($profile = $this->getProfile()) {
//            return $profile->getFirstname().' '.$profile->getLastname();
//        }

        return parent::__toString();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return User
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Set anonymous.
     *
     * @param bool $anonymous
     *
     * @return User
     */
    public function setAnonymous(bool $anonymous)
    {
        $this->anonymous = $anonymous;

        return $this;
    }

    /**
     * Returns true if the user is anonymous.
     *
     * @return bool
     */
    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    /**
     * @return string
     */
    public function getEncoder(): string
    {
        return $this->encoder;
    }

    /**
     * @param string $encoder
     */
    public function setEncoder(string $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @return string
     */
    public function getEncoderName(): string
    {
        return $this->encoder;
    }

}