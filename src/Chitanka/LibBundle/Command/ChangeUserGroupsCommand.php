<?php
namespace Chitanka\LibBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeUserGroupsCommand extends CommonDbCommand {

	protected function configure() {
		parent::configure();
		$this->setName('sys:change-user-groups')
			->setDescription('Change groups for given users')
			->addArgument('users', InputArgument::REQUIRED, 'Users which groups should be modified (comma separated)')
			->addArgument('groups', InputArgument::REQUIRED, 'Groups to add or remove (comma separated). Ex.: "+workroom-admin,-admin" adds the user to "workroom-admin" and removes him from "admin"')
		;
	}

	/** @inheritdoc */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$userNames = $this->readUsers($input);
		list($groupsToAdd, $groupsToRemove) = $this->readGroups($input);
		$users = $this->getUserRepository()->findByUsernames($userNames);
		$this->modifyUserGroups($users, $groupsToAdd, $groupsToRemove);
		$output->writeln("Done.");
	}

	protected function modifyUserGroups($users, $groupsToAdd, $groupsToRemove) {
		$em = $this->getEntityManager();
		foreach ($users as $user) {
			$user->addGroups($groupsToAdd);
			$user->removeGroups($groupsToRemove);
			$em->persist($user);
		}
		$em->flush();
	}

	protected function readUsers(InputInterface $input) {
		return array_map('trim', explode(',', $input->getArgument('users')));
	}

	protected function readGroups(InputInterface $input) {
		$groupsToAdd = $groupsToRemove = array();
		foreach (array_map('trim', explode(',', $input->getArgument('groups'))) as $groupIdent) {
			switch ($groupIdent[0]) {
				case '-':
					$groupsToRemove[] = substr($groupIdent, 1);
					break;
				case '+':
					$groupsToAdd[] = substr($groupIdent, 1);
					break;
				default:
					$groupsToAdd[] = $groupIdent;
			}
		}
		return array($groupsToAdd, $groupsToRemove);
	}

	/** @return \Chitanka\LibBundle\Entity\UserRepository */
	protected function getUserRepository() {
		return $this->getRepository('User');
	}
}
