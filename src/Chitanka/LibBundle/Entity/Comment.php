<?php
namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager;
use FOS\CommentBundle\Entity\Comment as BaseComment;
use FOS\CommentBundle\Model\SignedCommentInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="comment")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Comment extends BaseComment implements SignedCommentInterface
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	protected $id;

	/**
	 * Thread of this comment
	 *
	 * @ORM\ManyToOne(targetEntity="Chitanka\LibBundle\Entity\Thread")
	 * @var Thread
	 */
	protected $thread;

	/**
	 * Author of the comment
	 *
	 * @ORM\ManyToOne(targetEntity="Chitanka\LibBundle\Entity\User")
	 * @var User
	 */
	protected $author;

	protected $cc;

	public function setAuthor(UserInterface $author)
	{
		$this->author = $author;
	}

	public function getAuthor()
	{
		return $this->author;
	}

	public function getAuthorName()
	{
		if (null === $this->getAuthor()) {
			return 'Anonymous';
		}

		return $this->getAuthor()->getUsername();
	}

	public function isForWorkEntry()
	{
		return $this->getThread()->isForWorkEntry();
	}

	public function getWorkEntry()
	{
		return $this->getThread()->getWorkEntry();
	}

	public function setCc($cc)
	{
		$this->cc = $cc;
	}

	public function getCc()
	{
		return $this->cc;
	}

	public function hasParent()
	{
		return $this->getDepth() > 0;
	}

	public function isDeleted()
	{
		return $this->getState() === self::STATE_DELETED;
	}
}
