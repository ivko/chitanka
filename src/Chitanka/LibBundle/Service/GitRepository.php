<?php namespace Chitanka\LibBundle\Service;

class GitRepository extends \GitElephant\Repository {

	/**
	 * Commit content to the repository, eventually staging all unstaged content
	 *
	 * @param string $message  the commit message
	 * @param string $author   author of the commit
	 * @param bool   $stageAll whether to stage on not everything before commit
	 *
	 * @return GitRepository
	 */
	public function commitWithAuthor($message, $author, $stageAll = false) {
		if ($stageAll) {
			$this->stage();
		}
		$command = new GitCommitCommand;
		$this->getCaller()->execute($command->commitWithAuthor($message, $author, $stageAll));

		return $this;
	}
}
