<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\TextRevisionRepository")
* @ORM\Table(name="text_revision",
*	indexes={
*		@ORM\Index(name="text_idx", columns={"text_id"}),
*		@ORM\Index(name="user_idx", columns={"user_id"}),
*		@ORM\Index(name="date_idx", columns={"date"})}
* )
*/
class TextRevision extends Entity
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	private $id;

	/**
	 * @var integer $text
	 * @ORM\ManyToOne(targetEntity="Text", inversedBy="revisions")
	 */
	private $text;

	/**
	 * @var integer $user
	 * @ORM\ManyToOne(targetEntity="User")
	 */
	private $user;

	/**
	 * @var string $comment
	 * @ORM\Column(type="string", length=255)
	 */
	private $comment;

	/**
	 * @var datetime $date
	 * @ORM\Column(type="datetime")
	 */
	private $date;

	/**
	 * @var boolean
	 * @ORM\Column(type="boolean")
	 */
	private $first = true;


	public function getId() { return $this->id; }

	public function setText($text) { $this->text = $text; }
	public function getText() { return $this->text; }

	public function setUser($user) { $this->user = $user; }
	public function getUser() { return $this->user; }

	public function setComment($comment) { $this->comment = $comment; }
	public function getComment() { return $this->comment; }

	public function setDate($date) { $this->date = $date; }
	public function getDate() { return $this->date; }

	public function setFirst($first) { $this->first = $first; }
	public function getFirst() { return $this->first; }

	public function __toString()
	{
		return sprintf('%s (%s, %s)', $this->getComment(), $this->getUser(), $this->getDate()->format('Y-m-d H:i:s'));
	}
}
