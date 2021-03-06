<?php
namespace Chitanka\LibBundle\Service;

use Chitanka\LibBundle\Legacy\CacheManager;
use Chitanka\LibBundle\Legacy\DownloadFile;
use Chitanka\LibBundle\Legacy\Legacy;
use Chitanka\LibBundle\Legacy\ZipFile;
use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\File;
use Chitanka\LibBundle\Entity\TextRepository;

class TextDownloadService {

	private $textRepo;

	public function __construct(TextRepository $textRepo) {
		$this->textRepo = $textRepo;
	}

	public function getTxtZipFile($id, $format, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createTxtDlFile();
	}

	public function getSfbZipFile($id, $format, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createSfbDlFile();
	}

	public function getFb2ZipFile($id, $format, $requestedFilename) {
		$this->initZipData($id, $requestedFilename);
		return $this->createFb2DlFile();
	}

	public function getEpubFile($textId, $format, $requestedFilename) {
		$file = null;
		$dlFile = new DownloadFile;
		if ( count($textId) > 1 ) {
			$file = $dlFile->getEpubForTexts($this->textRepo->findBy(array('id' => $textId)));
		} else if ( $text = $this->textRepo->find($textId[0]) ) {
			$file = $dlFile->getEpubForText($text);
		}

		if ($file) {
			return $file;
		}
		return null;
	}

	/** Sfb */
	private function createSfbDlFile() {
		$key = '';
		$key .= '-sfb';
		return $this->createDlFile($this->textIds, 'sfb', $key);
	}

	private function addSfbToDlFileFromCache($textId) {
		$fEntry = unserialize( CacheManager::getDlCache($textId), '.sfb' );
		$this->zf->addFileEntry($fEntry);
		if ( $this->withFbi ) {
			$this->zf->addFileEntry(
				unserialize( CacheManager::getDlCache($textId, '.fbi') ) );
		}
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addSfbToDlFileFromNew($textId) {
		$mainFileData = $this->getMainFileData($textId);
		if ( ! $mainFileData ) {
			return false;
		}
		list($this->filename, $this->fPrefix, $this->fSuffix, $fbi) = $mainFileData;
		$this->addTextFileEntry($textId, '.sfb');
//		if ( $this->withFbi ) {
//			$this->addMiscFileEntry($fbi, $textId, '.fbi');
//		}
		return true;
	}

	private function addSfbToDlFileEnd($textId) {
		$this->addBinaryFileEntries($textId, $this->filename);
		return true;
	}


	/** Fb2 */
	private function createFb2DlFile() {
		return $this->createDlFile($this->textIds, 'fb2');
	}

	private function addFb2ToDlFileFromCache($textId) {
		$fEntry = unserialize( CacheManager::getDlCache($textId, '.fb2') );
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addFb2ToDlFileFromNew($textId) {
		$work = $this->textRepo->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsFb2(), $textId, '.fb2');
		return true;
	}


	/** Txt */
	private function createTxtDlFile() {
		return $this->createDlFile($this->textIds, 'txt');
	}

	private function addTxtToDlFileFromCache($textId) {
		$fEntry = unserialize( CacheManager::getDlCache($textId, '.txt') );
		$this->zf->addFileEntry($fEntry);
		$this->filename = $this->rmFEntrySuffix($fEntry['name']);
		return true;
	}

	private function addTxtToDlFileFromNew($textId) {
		$work = $this->textRepo->find($textId);
		if ( ! $work ) {
			return false;
		}
		$this->filename = $this->getFileName($work);
		$this->addMiscFileEntry($work->getContentAsTxt(), $textId, '.txt');
		return true;
	}


	/** Common */
	private function createDlFile($textIds, $format, $dlkey = null) {
		$textIds = $this->normalizeTextIds($textIds);
		if ($dlkey === null) {
			$dlkey = ".$format";
		}

		$dlCache = CacheManager::getDl($textIds, $dlkey);
		if ( $dlCache ) {
			$dlFile = CacheManager::getDlFile($dlCache);
			if ( file_exists($dlFile) && filesize($dlFile) ) {
				return $dlFile;
			}
		}

		$fileCount = count($textIds);
		$setZipFileName = $fileCount == 1 && empty($this->zipFileName);

		foreach ($textIds as $textId) {
			$method = 'add' . ucfirst($format) . 'ToDlFileFrom';
			$method .= CacheManager::dlCacheExists($textId, ".$format") ? 'Cache' : 'New';
			if ( ! $this->$method($textId) ) {
				$fileCount--; // no file was added
				continue;
			}
			$sharedMethod = 'add' . ucfirst($format) . 'ToDlFileEnd';
			if ( method_exists($this, $sharedMethod) ) {
				$this->$sharedMethod($textId);
			}
			if ($setZipFileName) {
				$this->zipFileName = $this->filename;
			}
		}
		if ( $fileCount < 1 ) {
			// TODO remove
			$this->addMessage('Не е посочен валиден номер на текст за сваляне!', true);
			return null;
		}

		if ( ! $setZipFileName && empty($this->zipFileName) ) {
			$this->zipFileName = "Архив от Моята библиотека - $fileCount файла-".time();
		}

		$this->zipFileName .= $fileCount > 1 ? "-$format" : $dlkey;
		$this->zipFileName = File::cleanFileName( Char::cyr2lat($this->zipFileName) );
		$fullZipFileName = $this->zipFileName . '.zip';

		CacheManager::setDlFile($fullZipFileName, $this->zf->file());
		CacheManager::setDl($textIds, $fullZipFileName, $dlkey);
		return CacheManager::getDlFile($fullZipFileName);
	}

	private function normalizeTextIds($textIds) {
		foreach ($textIds as $key => $textId) {
			if ( ! $textId || ! is_numeric($textId) ) {
				unset($textIds[$key]);
			}
		}
		sort($textIds);
		$textIds = array_unique($textIds);
		return $textIds;
	}

	private function addTextFileEntry($textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($this->fPrefix .
			$this->getContentData($textId) .
			$this->fSuffix, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry));
		$this->zf->addFileEntry($fEntry);
	}

	private function addMiscFileEntry($content, $textId, $suffix = '.txt') {
		$fEntry = $this->zf->newFileEntry($content, $this->filename . $suffix);
		CacheManager::setDlCache($textId, serialize($fEntry), $suffix);
		$this->zf->addFileEntry($fEntry);
	}

	private function addBinaryFileEntries($textId, $filename) {
		// add images
		$dir = Legacy::getContentFilePath('img', $textId);
		if ( !is_dir($dir) ) { return; }
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$fullname = "$dir/$file";
				if ( $file[0] == '.' || $file[0] == '_' ||
					File::isArchive($file) || is_dir($fullname) ) { continue; }
				$fEntry = $this->zf->newFileEntry(file_get_contents($fullname), $file);
				$this->zf->addFileEntry($fEntry);
			}
			closedir($dh);
		}
	}


	private function getContentData($textId) {
		$fname = Legacy::getContentFilePath('text', $textId);
		if ( file_exists($fname) ) {
			return file_get_contents($fname);
		}
		return '';
	}


	private function getMainFileData($textId) {
		$work = $this->textRepo->find($textId);
		return array(
			$this->getFileName($work),
			$this->getFileDataPrefix($work, $textId),
			$this->getFileDataSuffix($work, $textId),
			$this->getTextFileStart() . $work->getFbi()
		);
	}

	public function getFileName($work = null) {
		if ( is_null($work) ) $work = $this->work;

		return $this->getUniqueFileName($work->getNameForFile());
	}

	private function getUniqueFileName($filename) {
		if ( isset( $this->_fnameCount[$filename] ) ) {
			$this->_fnameCount[$filename]++;
			$filename .= $this->_fnameCount[$filename];
		} else {
			$this->_fnameCount[$filename] = 1;
		}
		return $filename;
	}

	private function initZipData($textId, $requestedFilename = null) {
		$this->textIds = $textId;
		$this->zf = new ZipFile;
		if ($requestedFilename) {
			$this->zipFileName = "chitanka-$requestedFilename";
		}
		// track here how many times a filename occurs
		$this->_fnameCount = array();
	}

	public function getFileDataPrefix($work, $textId) {
		$prefix = $this->getTextFileStart()
			. "|\t" . $work->getAuthorNames() . "\n"
			. $work->getTitleAsSfb() . "\n\n\n";
		$anno = $work->getAnnotation();
		if ( !empty($anno) ) {
			$prefix .= "A>\n$anno\nA$\n\n\n";
		}
		return $prefix;
	}

	public function getFileDataSuffix(\Chitanka\LibBundle\Entity\Text $work, $textId) {
		$suffix = "\n"
			. "I>\n"
			. $work->getExtraInfoForDownload()
			. "I$\n";
		$suffix = preg_replace('/\n\n+/', "\n\n", $suffix);
		return $suffix;
	}

	public static function getTextFileStart() {
		return "\xEF\xBB\xBF" . // Byte order mark for some windows software
			"\t[Kodirane UTF-8]\n\n";
	}

	private function rmFEntrySuffix($fEntryName) {
		return preg_replace('/\.[^.]+$/', '', $fEntryName);
	}
}
