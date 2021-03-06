<?php
namespace Chitanka\LibBundle\Service;

use Chitanka\LibBundle\Entity\Text;
use Chitanka\LibBundle\Entity\User;
use Chitanka\LibBundle\Legacy\mlDatabase as LegacyDb;

class TextService {

	private $legacyDb;

	public function __construct(LegacyDb $db) {
		$this->legacyDb = $db;
	}

	/**
	 * Get similar texts based ot readers count.
	 * @param Text $text
	 * @param int $limit   Return up to this limit number of texts
	 * @param User $reader  Do not return texts marked as read by this reader
	 */
	public function findTextAlikes(Text $text, $limit = 10, User $reader = null) {
		$alikes = array();
		$qa = array(
			'SELECT'   => 'text_id, count(*) readers',
			'FROM'     => DBT_READER_OF .' r',
			'WHERE'    => array(
				'r.text_id' => array('<>', $text->getId()),
				'r.user_id IN ('
					. $this->legacyDb->selectQ(DBT_READER_OF, array('text_id' => $text->getId()), 'user_id')
					. ')',
			),
			'GROUP BY' => 'r.text_id',
			'ORDER BY' => 'readers DESC',
		);
		if ( is_object($reader) ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $this->legacyDb->selectQ(DBT_READER_OF, array('user_id' => $reader->getId()), 'text_id')
				. ')';
		}
		$res = $this->legacyDb->extselect($qa);
		$alikes = $textsInQueue = array();
		$lastReaders = 0;
		$count = 0;
		while ( $row = $this->legacyDb->fetchAssoc($res) ) {
			$count++;
			if ( $lastReaders > $row['readers'] ) {
				if ( $count > $limit ) {
					break;
				}
				$alikes = array_merge($alikes, $textsInQueue);
				$textsInQueue = array();
			}
			$textsInQueue[] = $row['text_id'];
			$lastReaders = $row['readers'];
		}

		if ( $count > $limit ) {
			$alikes = array_merge($alikes, $this->filterSimilarByLabel($text, $textsInQueue, $limit - count($alikes)));
		}

// 		if ( empty($texts) ) {
// 			$texts = $this->getSimilarByLabel($text, $limit, $reader);
// 		}

		return $alikes;
	}


	/**
	 * Get similar texts based ot readers count.
	 * @param Text $text
	 * @param int $limit   Return up to this limit number of texts
	 * @param User $reader  Do not return texts marked as read by this reader
	 */
	private function getSimilarByLabel(Text $text, $limit = 10, User $reader = null) {
		$qa = array(
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => array(
				'text_id' => array('<>', $text->getId()),
				'label_id IN ('
					. $this->legacyDb->selectQ(DBT_TEXT_LABEL, array('text_id' => $text->getId()), 'label_id')
					. ')',
			),
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		);
		if ( $reader ) {
			$qa['WHERE'][] = 'text_id NOT IN ('
				. $this->legacyDb->selectQ(DBT_READER_OF, array('user_id' => $reader->getId()), 'text_id')
				. ')';
		}
		$res = $this->legacyDb->extselect($qa);
		$texts = array();
		while ($row = $this->legacyDb->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}


	private function filterSimilarByLabel(Text $text, $texts, $limit) {
		$qa = array(
			'SELECT'   => 'text_id',
			'FROM'     => DBT_TEXT_LABEL,
			'WHERE'    => array(
				'text_id' => array('IN', $texts),
				'label_id IN ('
					. $this->legacyDb->selectQ(DBT_TEXT_LABEL, array('text_id' => $text->getId()), 'label_id')
					. ')',
			),
			'GROUP BY' => 'text_id',
			'ORDER BY' => 'COUNT(text_id) DESC',
			'LIMIT'    => $limit,
		);
		$res = $this->legacyDb->extselect($qa);
		$texts = array();
		while ($row = $this->legacyDb->fetchRow($res)) {
			$texts[] = $row[0];
		}
		return $texts;
	}

}
