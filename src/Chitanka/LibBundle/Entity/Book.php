<?php

namespace Chitanka\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Legacy\Setup;
use Chitanka\LibBundle\Util\Ary;
use Sfblib_SfbConverter as SfbConverter;
use Sfblib_SfbToHtmlConverter as SfbToHtmlConverter;
use Sfblib_SfbToFb2Converter as SfbToFb2Converter;
use Symfony\Component\Validator\Constraints as Assert;

/**
* @ORM\Entity(repositoryClass="Chitanka\LibBundle\Entity\BookRepository")
* @ORM\HasLifecycleCallbacks
* @ORM\Table(name="book",
*	indexes={
*		@ORM\Index(name="title_idx", columns={"title"}),
*		@ORM\Index(name="title_author_idx", columns={"title_author"}),
*		@ORM\Index(name="subtitle_idx", columns={"subtitle"}),
*		@ORM\Index(name="orig_title_idx", columns={"orig_title"})}
* )
*/
class Book extends BaseWork
{
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="CUSTOM")
	 * @ORM\CustomIdGenerator(class="Chitanka\LibBundle\Doctrine\CustomIdGenerator")
	 */
	protected $id;

	/**
	 * @var string $slug
	 * @ORM\Column(type="string", length=50)
	 */
	private $slug;

	/**
	 * @var string $title_author
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $title_author;

	/**
	 * @var string $title
	 * @ORM\Column(type="string", length=255)
	 */
	private $title = '';

	/**
	 * @var string $subtitle
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $subtitle;

	/**
	 * @var string
	 * @ORM\Column(type="string", length=1000, nullable=true)
	 */
	private $title_extra;

	/**
	 * @var string $orig_title
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	private $orig_title;

	/**
	 * @var string $lang
	 * @ORM\Column(type="string", length=2)
	 */
	private $lang;

	/**
	 * @var string $orig_lang
	 * @ORM\Column(type="string", length=3, nullable=true)
	 */
	private $orig_lang;

	/**
	 * @var integer $year
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $year;

	/**
	 * @var integer $trans_year
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $trans_year;

	/**
	 * @var string $type
	 * @ORM\Column(type="string", length=10)
	 */
	private $type;
	static private $typeList = array(
		'single' => 'Обикновена книга',
		'collection' => 'Сборник',
		'poetry' => 'Стихосбирка',
		'anthology' => 'Антология',
		'magazine' => 'Списание',
	);


	/**
	 * @var integer
	 * @ORM\ManyToOne(targetEntity="Sequence", inversedBy="books")
	 */
	private $sequence;

	/**
	 * @var integer
	 * @ORM\Column(type="smallint", nullable=true)
	 */
	private $seqnr;

	/**
	 * @var integer
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="books")
	 */
	private $category;

	/**
	 * @var boolean
	 * @ORM\Column(type="boolean")
	 */
	private $has_anno;

	/**
	 * @var boolean
	 * @ORM\Column(type="boolean")
	 */
	private $has_cover;

	/**
	 * List of formats in which the book is available
	 * @ORM\Column(type="array")
	 */
	private $formats = array();

	/**
	 * @ORM\OneToMany(targetEntity="BookRevision", mappedBy="book", cascade={"persist"})
	 */
	private $revisions;

	/**
	 * A notice if the content is removed
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $removed_notice;

	/*
	 * @ORM\ManyToMany(targetEntity="Person", inversedBy="books")
	 * @ORM\JoinTable(name="book_author")
	 */
	private $authors;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookAuthor", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 * @ORM\OrderBy({"pos" = "ASC"})
	 */
	private $bookAuthors;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookText", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $bookTexts;

	/** FIXME doctrine:schema:create does not allow this relation
	 * @ORM\ManyToMany(targetEntity="Text", inversedBy="books")
	 * @ORM\JoinTable(name="book_text",
	 *	joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id")},
	 *	inverseJoinColumns={@ORM\JoinColumn(name="text_id", referencedColumnName="id")})
	 */
	private $texts;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="BookLink", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private $links;

	/**
	 * @var date
	 * @ORM\Column(type="date")
	 */
	private $created_at;

	private $fb2CoverWidth = 400;


	public function __construct()
	{
		$this->bookAuthors = new ArrayCollection;
		$this->bookTexts = new ArrayCollection;
		$this->links = new ArrayCollection;
	}

	public function __toString()
	{
		return $this->title;
	}

	public function getId() { return $this->id; }

	public function setSlug($slug) { $this->slug = String::slugify($slug); }
	public function getSlug() { return $this->slug; }

	public function setTitleAuthor($titleAuthor) { $this->title_author = $titleAuthor; }
	public function getTitleAuthor() { return $this->title_author; }

	public function setTitle($title) { $this->title = $title; }
	public function getTitle() { return $this->title; }

	public function setSubtitle($subtitle) { $this->subtitle = $subtitle; }
	public function getSubtitle() { return $this->subtitle; }

	public function setTitleExtra($title) { $this->title_extra = $title; }
	public function getTitleExtra() { return $this->title_extra; }

	public function setOrigTitle($origTitle) { $this->orig_title = $origTitle; }
	public function getOrigTitle() { return $this->orig_title; }

	public function setLang($lang) { $this->lang = $lang; }
	public function getLang() { return $this->lang; }

	public function setOrigLang($origLang) { $this->orig_lang = $origLang; }
	public function getOrigLang() { return $this->orig_lang; }

	public function setYear($year) { $this->year = $year; }
	public function getYear() { return $this->year; }

	public function setTransYear($transYear) { $this->trans_year = $transYear; }
	public function getTransYear() { return $this->trans_year; }

	public function setType($type) { $this->type = $type; }
	public function getType() { return $this->type; }

	public function setFormats($formats) { $this->formats = $formats; }
	public function getFormats() { return $this->formats; }
	public function isInSfbFormat()
	{
		return in_array('sfb', $this->formats);
	}

	public function getRevisions() { return $this->revisions; }
	public function addRevision(BookRevision $revision)
	{
		$this->revisions[] = $revision;
	}

	public function setRemovedNotice($removed_notice) { $this->removed_notice = $removed_notice; }
	public function getRemovedNotice() { return $this->removed_notice; }

	public function getAuthors()
	{
		if (!isset($this->authors)) {
			$this->authors = array();
			foreach ($this->getBookAuthors() as $author) {
				if ($author->getPos() >= 0) {
					$this->authors[] = $author->getPerson();
				}
			}
		}
		return $this->authors;
	}

	public function getAuthorsPlain($separator = ', ')
	{
		$authors = array();
		foreach ($this->getAuthors() as $author) {
			$authors[] = $author->getName();
		}

		return implode($separator, $authors);
	}

	public function addAuthor($author)
	{
		$this->authors[] = $author;
	}

	public function addBookAuthor(BookAuthor $bookAuthor)
	{
		$this->bookAuthors[] = $bookAuthor;
	}
	public function removeBookAuthor(BookAuthor $bookAuthor)
	{
		$this->bookAuthors->removeElement($bookAuthor);
	}
	// TODO needed by admin; why?
	public function addBookAuthors(BookAuthor $bookAuthor) { $this->addBookAuthor($bookAuthor); }

	public function setBookAuthors($bookAuthors) { $this->bookAuthors = $bookAuthors; }
	public function getBookAuthors() { return $this->bookAuthors; }

	public function getBookTexts() { return $this->bookTexts; }
	public function setBookTexts($bookTexts) { $this->bookTexts = $bookTexts; }
	public function addBookText(BookText $bookText)
	{
		$this->bookTexts[] = $bookText;
	}

	public function getTexts() { return $this->texts; }
	public function setTexts($texts)
	{
		$bookTexts = $this->getBookTexts();
		foreach ($texts as $key => $text) {
			$bookText = $bookTexts->get($key);
			if ($bookText === null) {
				$bookText = new BookText;
				$bookText->setBook($this);
				$bookText->setShareInfo(true);
				$this->addBookText($bookText);
			}
			$bookText->setText($text);
			$bookText->setPos($key);
		}
		$keysToRemove = array_diff($bookTexts->getKeys(), array_keys($texts));
		foreach ($keysToRemove as $key) {
			$bookTexts->remove($key);
		}
		$this->textsNeedUpdate = false;
	}

	private $textsNeedUpdate = false;
	public function textsNeedUpdate()
	{
		return $this->textsNeedUpdate;
	}

	public function setLinks($links) { $this->links = $links; }
	public function getLinks() { return $this->links; }
	public function addLink(BookLink $link)
	{
		$this->links[] = $link;
	}
	public function removeLink(BookLink $link)
	{
		$this->links->removeElement($link);
	}
	// TODO needed by admin; why?
	public function addLinks(BookLink $link) { $this->addLink($link); }

	public function setHasAnno($has_anno) { $this->has_anno = $has_anno; }
	public function getHasAnno() { return $this->has_anno; }
	public function hasAnno() { return $this->has_anno; }

	public function setHasCover($has_cover) { $this->has_cover = $has_cover; }
	public function getHasCover() { return $this->has_cover; }
	public function hasCover() { return $this->has_cover; }

	public function setSequence($sequence) { $this->sequence = $sequence; }
	public function getSequence() { return $this->sequence; }
	public function getSequenceSlug()
	{
		return $this->sequence ? $this->sequence->getSlug() : null;
	}

	public function setSeqnr($seqnr) { $this->seqnr = $seqnr; }
	public function getSeqnr() { return $this->seqnr; }

	public function setCategory($category) { $this->category = $category; }
	public function getCategory() { return $this->category; }
	public function getCategorySlug()
	{
		return $this->category ? $this->category->getSlug() : null;
	}

	public function setCreatedAt($created_at) { $this->created_at = $created_at; }
	public function getCreatedAt() { return $this->created_at; }

	public function getSfbg()
	{
		return $this->getLink('SFBG');
	}

	public function getPuk()
	{
		return $this->getLink('ПУК!');
	}

	public function getLink($name)
	{
		$links = $this->getLinks();
		foreach ($links as $link) {
			if ($link->getSiteName() == $name) {
				return $link;
			}
		}

		return null;
	}

	private $textIds = array();
	private $textsById = array();

	static protected $annotationDir = 'book-anno';
	static protected $infoDir = 'book-info';
	protected $covers = array();



	public function getDocId()
	{
		return 'http://chitanka.info/book/' . $this->id;
	}

	//public function getType() { return 'book'; }

	public function getAuthor()
	{
		return $this->title_author;
	}


	public function getMainAuthors()
	{
		if ( ! isset($this->mainAuthors) ) {
			$this->mainAuthors = array();
			foreach ($this->getTextsById() as $text) {
				if ( self::isMainWorkType($text->getType()) ) {
					foreach ($text->getAuthors() as $author) {
						$this->mainAuthors[$author->getId()] = $author;
					}
				}
			}
		}

		return $this->mainAuthors;
	}

	public function getAuthorSlugs()
	{
		$slugs = array();
		foreach ($this->getAuthors() as $author/*@var $author Person*/) {
			$slugs[] = $author->getSlug();
		}
		return $slugs;
	}

	static public function isMainWorkType($type)
	{
		return ! in_array($type, array('intro', 'outro'/*, 'interview', 'article'*/));
	}


	public function getAuthorsBy($type)
	{
		if ( ! isset($this->authorsBy[$type]) ) {
			$this->authorsBy[$type] = array();
			foreach ($this->getTextsById() as $text) {
				if ($text->getType() == $type) {
					foreach ($text->getAuthors() as $author) {
						$this->authorsBy[$type][$author['id']] = $author;
					}
				}
			}
		}

		return $this->authorsBy[$type];
	}


	public function getTranslators()
	{
		if ( ! isset($this->translators) ) {
			$this->translators = array();
			$seen = array();
			foreach ($this->getTexts() as $text) {
				foreach ($text->getTranslators() as $translator) {
					if ( ! in_array($translator->getId(), $seen) ) {
						$this->translators[] = $translator;
						$seen[] = $translator->getId();
					}
				}
			}
		}

		return $this->translators;
	}

	public function getLangOld()
	{
		if ( ! isset($this->lang) ) {
			$langs = array();
			foreach ($this->getTextsById() as $text) {
				if ( ! isset($langs[$text->lang]) ) {
					$langs[$text->lang] = 0;
				}
				$langs[$text->lang]++;
			}

			arsort($langs);
			list($this->lang,) = each($langs);
		}

		return $this->lang;
	}

	public function getOrigLangOld()
	{
		if ( ! isset($this->orig_lang) ) {
			$langs = array();
			foreach ($this->getTextsById() as $text) {
				if ( ! isset($langs[$text->orig_lang]) ) {
					$langs[$text->orig_lang] = 0;
				}
				$langs[$text->orig_lang]++;
			}

			arsort($langs);
			list($this->orig_lang,) = each($langs);
		}

		return $this->orig_lang;
	}

	public function getYearOld()
	{
		if ( ! isset($this->year) ) {
			$texts = $this->getTextsById();
			$text = array_shift($texts);
			$this->year = $text->year;
		}

		return $this->year;
	}

	public function getTransYearOld()
	{
		if ( ! isset($this->trans_year) ) {
			$texts = $this->getTextsById();
			$text = array_shift($texts);
			$this->trans_year = $text->trans_year;
		}

		return $this->trans_year;
	}


	static public function newFromId($id)
	{
		$db = Setup::db();
		$res = $db->select(DBT_BOOK, array('id' => $id));
		$data = $db->fetchAssoc($res);
		$book = new Book;
		foreach ($data as $field => $value) {
			$book->$field = $value;
		}

		return $book;
	}


	static public function newFromArray($fields)
	{
		$book = new Book;
		foreach ($fields as $field => $value) {
			$book->$field = $value;
		}

		return $book;
	}


	public function withAutohide()
	{
		return $this->getTemplate()->hasAutohide();
	}

	public function getTemplateAsXhtml()
	{
		return $this->getTemplate()->getAsXhtml();
	}

	private $template;
	/** @return Content\BookTemplate */
	public function getTemplate()
	{
		return $this->template ?: $this->template = new Content\BookTemplate($this);
	}

	public function getRawTemplate()
	{
		return $this->getTemplate()->getContent();
	}

	public function setRawTemplate($template)
	{
		$this->getTemplate()->setContent($template);
		$this->textsNeedUpdate = true;
	}

	public function getTextIdsFromTemplate()
	{
		return $this->getTemplate()->getTextIds();
	}

	public function getCover($width = null)
	{
		$this->initCovers();

		return is_null($width) ? $this->covers['front'] : Legacy::genThumbnail($this->covers['front'], $width);
	}

	public function getBackCover($width = null)
	{
		$this->initCovers();

		return is_null($width) ? $this->covers['back'] : Legacy::genThumbnail($this->covers['back'], $width);
	}


	static protected $exts = array('.jpg');

	public function initCovers()
	{
		if (empty($this->covers)) {
			$this->covers['front'] = $this->covers['back'] = null;

			$covers = self::getCovers($this->id);
			if ( ! empty($covers)) {
				$this->covers['front'] = $covers[0];
			} else {
				// there should not be any covers by texts
				/*foreach ($this->getTextIds() as $textId) {
					$covers = self::getCovers($textId);
					if ( ! empty($covers) ) {
						$this->covers['front'] = $covers[0];
						break;
					}
				}*/
			}

			if ($this->covers['front']) {
				$back = preg_replace('/(.+)\.(\w+)$/', '$1-back.$2', $this->covers['front']);
				if (file_exists($back)) {
					$this->covers['back'] = $back;
				}
			}
		}
	}

	/**
	 * @param $id Text or book ID
	 * @param $defCover Default covers if there aren’t any for $id
	 */
	static public function getCovers($id, $defCover = null)
	{
		$key = 'book-cover-content';
		$bases = array(Legacy::getContentFilePath($key, $id));
		if ( ! empty($defCover)) {
			$bases[] = Legacy::getContentFilePath($key, $defCover);
		}
		$coverFiles = Ary::cartesianProduct($bases, self::$exts);
		$covers = array();
		foreach ($coverFiles as $file) {
			if (file_exists($file)) {
				$covers[] = $file;
				// search for more images of the form “ID-DIGIT.EXT”
				for ($i = 2; /* infinity */; $i++) {
					$efile = strtr($file, array('.' => "-$i."));
					if (file_exists($efile)) {
						$covers[] = $efile;
					} else {
						break;
					}
				}
				break; // don’t check other extensions
			}
		}
		return $covers;
	}

	static public function renameCover($cover, $newname) {
		$rexts = strtr(implode('|', self::$exts), array('.'=>'\.'));
		return preg_replace("/\d+(-\d+)?($rexts)/", "$newname$1$2", $cover);
	}


	public function getImages()
	{
		return array_merge($this->getLocalImages(), $this->getTextImages());
	}

	public function getThumbImages()
	{
		return $this->getTextThumbImages();
	}

	public function getLocalImages()
	{
		$images = array();

		$dir = Legacy::getInternalContentFilePath('book-img', $this->id);
		foreach (glob("$dir/*") as $img) {
			$images[] = $img;
		}

		return $images;
	}

	public function getTextImages()
	{
		$images = array();

		foreach ($this->getTexts() as $text) {
			$images = array_merge($images, $text->getImages());
		}

		return $images;
	}

	public function getTextThumbImages()
	{
		$images = array();

		foreach ($this->getTexts() as $text) {
			$images = array_merge($images, $text->getThumbImages());
		}

		return $images;
	}

	public function getLabels()
	{
		$labels = array();

		foreach ($this->getTexts() as $text) {
			foreach ($text->getLabels() as $label) {
				$labels[] = $label->getName();
			}
		}

		$labels = array_unique($labels);

		return $labels;
	}


	public function getContentAsSfb()
	{
		if (!$this->isInSfbFormat()) {
			return null;
		}
		return $this->getTitleAsSfb() . SfbConverter::EOL
			. $this->getAllAnnotationsAsSfb()
			. $this->getMainBodyAsSfb()
			. $this->getInfoAsSfb();
	}


	public function getMainBodyAsSfb()
	{
		return $this->getTemplate()->generateSfb();
	}


	public function getMainBodyAsSfbFile()
	{
		if ( isset($this->_mainBodyAsSfbFile) ) {
			return $this->_mainBodyAsSfbFile;
		}

		$this->_mainBodyAsSfbFile = tempnam(sys_get_temp_dir(), 'book-');
		file_put_contents($this->_mainBodyAsSfbFile, $this->getMainBodyAsSfb());

		return $this->_mainBodyAsSfbFile;
	}


	/**
	 * Return the author of a text if he/she is not on the book title
	 */
	public function getBookAuthorIfNotInTitle($text)
	{
		$bookAuthorsIds = $this->getAuthorIds();
		$authors = array();
		foreach ($text->getAuthors() as $author) {
			if ( ! in_array($author->getId(), $bookAuthorsIds)) {
				$authors[] = $author;
			}
		}

		return $authors;
	}


	public function getTitleAsSfb()
	{
		$sfb = '';
		$prefix = SfbConverter::HEADER . SfbConverter::CMD_DELIM;

		if ('' != $authors = $this->getAuthorsPlain()) {
			$sfb .= $prefix . $authors . SfbConverter::EOL;
		}

		$sfb .= $prefix . $this->title . SfbConverter::EOL;

		if ( ! empty($this->subtitle) ) {
			$sfb .= $prefix . $this->subtitle . SfbConverter::EOL;
		}

		return $sfb;
	}


	public function getAllAnnotationsAsSfb()
	{
		if ( ($text = $this->getAnnotationAsSfb()) ) {
			return $text;
		}

		return $this->getTextAnnotations();
	}

	public function getAnnotationAsXhtml($imgDir = null)
	{
		if ($imgDir === null) {
			$imgDir = 'IMG_DIR_PREFIX' . Legacy::getContentFilePath('book-img', $this->id);
		}
		return parent::getAnnotationAsXhtml($imgDir);
	}


	/* TODO remove: there should not be any annotations by texts */
	public function getTextAnnotations()
	{
		return '';

		$annotations = array();
		foreach ($this->getTextsById() as $text) {
			$annotation = $text->getAnnotation();
			if ($annotation != '') {
				$annotations[$text->title] = $annotation;
			}
		}

		if (empty($annotations)) {
			return '';
		}

		$bannotation = '';
		$putTitles = count($annotations) > 1;
		foreach ($annotations as $title => $annotation) {
			if ($putTitles) {
				$bannotation .= SfbConverter::EOL . SfbConverter::EOL
					. SfbConverter::SUBHEADER . SfbConverter::CMD_DELIM . $title
					. SfbConverter::EOL;
			}
			$bannotation .= $annotation;
		}

		return SfbConverter::ANNO_S . SfbConverter::EOL
			. rtrim($bannotation) . SfbConverter::EOL
			. SfbConverter::ANNO_E . SfbConverter::EOL;
	}

	public function getInfoAsSfb()
	{
		return SfbConverter::INFO_S . SfbConverter::EOL
			. SfbConverter::CMD_DELIM . $this->getOriginMarker() . SfbConverter::EOL
			. rtrim($this->getExtraInfo()) . SfbConverter::EOL
			. SfbConverter::INFO_E . SfbConverter::EOL;
	}


	public function getOriginMarker()
	{
		return sprintf('Свалено от „Моята библиотека“: %s', $this->getDocId());
	}

	public function getContentAsFb2()
	{
		if (!$this->isInSfbFormat()) {
			return null;
		}
		$imgdir = $this->initTmpImagesDir();

		$conv = new SfbToFb2Converter($this->getContentAsSfb(), $imgdir);

		$conv->setObjectCount(1);
		$conv->setSubtitle($this->subtitle);
		$conv->setGenre($this->getGenresForFb2());
		$conv->setKeywords( implode(', ', $this->getLabels()) );
		$conv->setTextDate($this->getYear());

		if ( ($cover = $this->getCover($this->fb2CoverWidth)) ) {
			$conv->addCoverpage($cover);
		}

		$conv->setLang($this->getLang());
		$orig_lang = $this->getOrigLang();
		$conv->setSrcLang(empty($orig_lang) ? '?' : $orig_lang);

		foreach ($this->getTranslators() as $translator) {
			$conv->addTranslator($translator->getName());
		}

		$conv->setDocId($this->getDocId());
		$conv->setDocAuthor('Моята библиотека');

		$conv->enablePrettyOutput();

		$content = $conv->convert()->getContent();

		return $content;
	}

	private function getGenresForFb2()
	{
		$genres = array();
		foreach ($this->getTexts() as $text) {
			$genres = array_merge($genres, $text->getGenresForFb2());
		}
		$genres = array_unique($genres);
		return $genres;
	}

	public function getHeaders()
	{
		if ( isset($this->_headers) ) {
			return $this->_headers;
		}

		require_once __DIR__ . '/../Legacy/SfbParserSimple.php';
		$this->_headers = array();
		foreach (\Chitanka\LibBundle\Legacy\makeDbRows($this->getMainBodyAsSfbFile(), 4) as $row) {
			$header = new TextHeader;
			$header->setNr($row[0]);
			$header->setLevel($row[1]);
			$header->setName($row[2]);
			$header->setFpos($row[3]);
			$header->setLinecnt($row[4]);
			$this->_headers[] = $header;
		}

		return $this->_headers;
	}

	public function getEpubChunks($imgDir)
	{
		return $this->getEpubChunksFrom($this->getMainBodyAsSfbFile(), $imgDir);
	}


	public function initTmpImagesDir()
	{
		$dir = sys_get_temp_dir() . '/' . uniqid();
		mkdir($dir);
		foreach ($this->getImages() as $image) {
			copy($image, $dir.'/'.basename($image));
		}

		return $dir;
	}


	public function getNameForFile()
	{
		return trim("$this->title_author - $this->title - $this->subtitle-$this->id-b", '- ');
	}


	public function getTextIds()
	{
		if ( empty($this->textIds) ) {
			preg_match_all('/\{(text|file):(\d+)/', $this->getTemplate()->getContent(), $matches);
			$this->textIds = $matches[2];
		}

		return $this->textIds;
	}


	public function getTextById($textId)
	{
		$texts = $this->getTextsById();
		return isset($texts[$textId]) ? $texts[$textId] : null;
	}

	public function getTextsById()
	{
		if ( empty($this->textsById) ) {
			foreach ($this->getTextIds() as $id) {
				$this->textsById[$id] = null;
			}
			foreach ($this->getTexts() as $text) {
				$this->textsById[$text->getId()] = $text;
			}
			foreach ($this->textsById as $id => $text) {
				if (is_null($text)) {
					$text = new Text($id);
					$this->textsById[$id] = $text;
				}
			}
		}

		return $this->textsById;
	}


	public function isGamebook()
	{
		return false;
	}


	public function isFromSameAuthor($text)
	{
		return $this->getAuthorIds() == $text->getAuthorIds();
	}


	/** TODO set for a books with only one novel */
	public function getPlainSeriesInfo()
	{
		return '';
	}

	public function getPlainTranslationInfo()
	{
		$info = array();
		foreach ($this->getTranslators() as $translator) {
			$info[] = $translator->getName();
		}

		return sprintf('Превод: %s', implode(', ', $info));
	}


	public function getDataAsPlain()
	{
		$authors = implode($this->getAuthorSlugs());

		return <<<EOS
title       = {$this->getTitle()}
subtitle    = {$this->getSubtitle()}
title_extra = {$this->getTitleExtra()}
authors     = $authors
slug        = {$this->getSlug()}
lang        = {$this->getLang()}
orig_title  = {$this->getOrigTitle()}
orig_lang   = {$this->getOrigLang()}
year        = {$this->getYear()}
sequence    = {$this->getSequenceSlug()}
seq_nr      = {$this->getSeqnr()}
category    = {$this->getCategorySlug()}
type        = {$this->getType()}
id          = {$this->getId()}
EOS;
	}


	public function getDatafiles()
	{
		$files = array();
		$files['book'] = Legacy::getContentFilePath('book', $this->id);
		if ($this->hasCover()) {
			$files['book-cover'] = Legacy::getContentFilePath('book-cover', $this->id) . '.max.jpg';
		}
		if ($this->hasAnno()) {
			$files['book-anno'] = Legacy::getContentFilePath('book-anno', $this->id);
		}
		$files['book-info'] = Legacy::getContentFilePath('book-info', $this->id);

		return $files;
	}
	public function setDatafiles($f) {} // dummy for sonata admin

	public function getStaticFile($format)
	{
		if (!in_array($format, array('djvu', 'pdf'))) {
			throw new \Exception("Format $format is not a valid static format for a book.");
		}
		return Legacy::getContentFilePath('book-'.$format, $this->id);
	}

	##################
	# legacy pic stuff
	##################

	const
		MIRRORS_FILE = 'MIRRORS',
		INFO_FILE = 'INFO',
		THUMB_DIR = 'thumb',

		THUMBS_FILE_TPL = 'thumbs-%d.jpg',
		MAX_JOINED_THUMBS = 50;

	public function getSeriesName($pic = null) {
		if ( is_null($pic) ) {
			$pic = $this;
		}
		if ( empty($pic->series) ) {
			return '';
		}
		$name = $pic->seriesName;
		$picType = picType($pic->seriesType);
		if ( ! empty($picType) ) {
			$name = "$picType „{$name}“";
		}
		return $name;
	}


	public function getIssueName($pic = null) {
		if ( is_null($pic) ) {
			$pic = $this;
		}
		return $pic->__toString();
	}

	public function getFiles()
	{
		if ( isset($this->_files) ) {
			return $this->_files;
		}

		$dir = Legacy::getContentFilePath('book-pic', $this->id);

		$ignore = array(self::MIRRORS_FILE, self::THUMB_DIR);

		$files = array();
		foreach (scandir($dir) as $file) {
			if ( $file[0] == '.' || in_array($file, $ignore) ) {
				continue;
			}
			$files[] = $file;
		}

		sort($files);

		return $this->_files = $files;
	}


	// deprecated
	private function getMirrors()
	{
		return array();
	}

	public function getDocRoot($cache = true)
	{
		if ( isset($this->_docRoot) && $cache ) {
			return $this->_docRoot;
		}

		$mirrors = $this->getMirrors();
		if ( empty($mirrors) ) {
			$this->_docRoot = '';
		} else {
			shuffle($mirrors);
			$this->_docRoot = rtrim($mirrors[0], '/') . '/';
		}

		return $this->_docRoot;
	}


	public function getImageDir()
	{
		if ( ! isset($this->_imageDir) ) {
			$this->_imageDir = Legacy::getContentFilePath('book-pic', $this->id);
		}

		return $this->_imageDir;
	}


	public function getThumbDir()
	{
		if ( ! isset($this->_thumbDir) ) {
			$this->_thumbDir = $this->getImageDir() .'/'. self::THUMB_DIR;
		}

		return $this->_thumbDir;
	}


	public function getWebImageDir()
	{
		if ( ! isset($this->_webImageDir) ) {
			$this->_webImageDir = $this->getDocRoot() . $this->getImageDir();
		}

		return $this->_webImageDir;
	}


	public function getWebThumbDir()
	{
		if ( ! isset($this->_webThumbDir) ) {
			$this->_webThumbDir = $this->getDocRoot() . $this->getThumbDir();
		}

		return $this->_webThumbDir;
	}


	public function getThumbFile($currentPage)
	{
		$currentJoinedFile = floor($currentPage / self::MAX_JOINED_THUMBS);

		return sprintf(self::THUMBS_FILE_TPL, $currentJoinedFile);
	}

	public function getThumbClass($currentPage)
	{
		return 'th' . ($currentPage % self::MAX_JOINED_THUMBS);
	}


	public function getSiblings()
	{
		if ( isset($this->_siblings) ) {
			return $this->_siblings;
		}

		$qa = array(
			'SELECT' => 'p.*, s.name seriesName, s.type seriesType',
			'FROM' => DBT_PIC .' p',
			'LEFT JOIN' => array(
				DBT_PIC_SERIES .' s' => 'p.series = s.id'
			),
			'WHERE' => array(
				'series' => $this->series,
				'p.series' => array('>', 0),
			),
			'ORDER BY' => 'sernr ASC'
		);
		$db = Setup::db();
		$res = $db->extselect($qa);
		$siblings = array();
		while ( $row = $db->fetchAssoc($res) ) {
			$siblings[ $row['id'] ] = new PicWork($row);
		}

		return $this->_siblings = $siblings;
	}


	public function getNextSibling() {
		if ( empty($this->series) ) {
			return false;
		}
		$dbkey = array('series' => $this->series);
		if ($this->sernr == 0) {
			$dbkey['p.id'] = array('>', $this->id);
		} else {
			$dbkey[] = 'sernr = '. ($this->sernr + 1)
				. " OR (sernr > $this->sernr AND p.id > $this->id)";
		}
		return self::newFromDB($dbkey);
	}


	public function sameAs($otherPic)
	{
		return $this->id == $otherPic->id;
	}

	static public function getTypeList()
	{
		return self::$typeList;
	}

	private $revisionComment;
	public function getRevisionComment()
	{
		return $this->revisionComment;
	}

	public function setRevisionComment($comment)
	{
		$this->revisionComment = $comment;
	}

}
