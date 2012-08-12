<?php
namespace Chitanka\LibBundle\Admin;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chitanka\LibBundle\Entity\Book;
use Chitanka\LibBundle\Util\Language;

class BookAdmin extends Admin
{
	protected $baseRoutePattern = 'book';
	protected $baseRouteName = 'admin_book';
	protected $translationDomain = 'admin';

	protected function configureShowField(ShowMapper $showMapper)
	{
		$showMapper
			->add('slug')
			->add('title')
			->add('authors')
			->add('subtitle')
			->add('title_extra')
			->add('orig_title')
			->add('lang')
			->add('orig_lang')
			->add('year')
			->add('trans_year')
			->add('type')
			->add('sequence')
			->add('seqnr')
			->add('category')
			->add('removedNotice')
			->add('texts')
			->add('links')
			->add('created_at')
		;
	}

	protected function configureListFields(ListMapper $listMapper)
	{
		$listMapper
			->add('url', 'string', array('template' => 'LibBundle:BookAdmin:list_url.html.twig'))
			->add('slug')
			->addIdentifier('title')
			->add('id')
			->add('type')
			->add('sfbg', 'string', array('template' => 'LibBundle:BookAdmin:list_sfbg.html.twig'))
			->add('puk', 'string', array('template' => 'LibBundle:BookAdmin:list_puk.html.twig'))
			->add('_action', 'actions', array(
				'actions' => array(
					'view' => array(),
					'edit' => array(),
					'delete' => array(),
				)
			))
		;
	}

	public $preFormContent = 'LibBundle:BookAdmin:form_datafiles.html.twig';

	protected function configureFormFields(FormMapper $formMapper)
	{
		$formMapper
			//->add('sfbg', 'string', array('template' => 'LibBundle:BookAdmin:form_sfbg.html.twig'))
			//->add('datafiles', 'string', array('template' => 'LibBundle:BookAdmin:form_datafiles.html.twig'))
			->add('slug')
			->add('title')
			->add('bookAuthors', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
			))
			->add('subtitle', null, array('required' => false))
			->add('title_extra', null, array('required' => false))
			->add('orig_title', null, array('required' => false))
			->add('lang', 'choice', array('choices' => Language::getLangs()))
			->add('orig_lang', 'choice', array('required' => false, 'choices' => Language::getLangs()))
			->add('year')
			->add('trans_year', null, array('required' => false))
			->add('type', 'choice', array('choices' => Book::getTypeList()))
			->add('sequence', null, array('required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}))
			->add('seqnr', null, array('required' => false))
			->add('category', null, array('required' => false, 'query_builder' => function ($repo) {
				return $repo->createQueryBuilder('e')->orderBy('e.name');
			}))
			->add('removedNotice')
			->add('links', 'sonata_type_collection', array(
				'by_reference' => false,
				'required' => false,
			), array(
				'edit' => 'inline',
				'inline' => 'table',
				'sortable' => 'site_id'
			))
		;
	}

	protected function configureDatagridFilters(DatagridMapper $datagrid)
	{
		$datagrid
			->add('title')
			->add('subtitle')
			->add('type')
			->add('has_cover')
			->add('has_anno')
		;
	}

	public function preUpdate($book) {
		foreach ($book->getLinks() as $link) {
			$link->setBook($book);
		}
		foreach ($book->getBookAuthors() as $bookAuthor) {
			if ($bookAuthor->getPerson()) {
				$bookAuthor->setBook($book);
			}
		}
	}

}
