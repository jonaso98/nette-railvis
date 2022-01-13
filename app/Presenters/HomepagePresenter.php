<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class HomepagePresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}
    
    public function renderDefault(string $postValue = null, int $page = 1): void
    {
        if ($postValue) {
            $postsCount = $this->database
            ->table('posts')
            ->where('posts.created_at < ?',  new \DateTime)
            ->whereOr(['posts.content LIKE ?' => '%'.$postValue.'%', 'category.name LIKE ?' => '%'.$postValue.'%'])
            ->count();
            $this->template->postValue = $postValue;
        } else {
            $postsCount = $this->database
            ->table('posts')
            ->count();
            $this->template->postValue = null;
        }

        
        
		// We'll make the Paginator instance and set it up
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($postsCount); // total articles count
		$paginator->setItemsPerPage(1); // items per page
		$paginator->setPage($page); // actual page number

        $this->template->post_links = $this->database
            ->fetchAll('Select pl.* from post_external_links as pl');

        $post = $this->database->table('posts');
        $post->where('posts.created_at < ?',  new \DateTime);

        if ($postValue) {
            $post->whereOr(['posts.content LIKE ?' => '%'.$postValue.'%', 'category.name LIKE ?' => '%'.$postValue.'%']);
        }
        $post->limit($paginator->getLength(), $paginator->getOffset());
        $post->order('posts.created_at DESC');
        $post->select('posts.id, posts.id_category, posts.content, posts.created_at, category.name AS category_name, region.name AS region_name');

    if (!$post) {
        $this->error('Post not found');
    }
    $this->template->posts = $post;
    $this->template->paginator = $paginator;
    }

    public function renderShow(int $postId): void
	{
		$this->template->post = $this->database
			->table('posts')
			->get($postId);
	}
    protected function createComponentKlientSearchForm() {

		$form = new Form;
		$form->addText('search')->setRequired(TRUE)
        ->setHtmlAttribute('class', 'form-control')
        ->setHtmlAttribute('placeholder', 'Search');
		$form->addSubmit('send', 'Search');
		$form->onSuccess[] = [$this, 'klientSearchFormSucceeded'];
		return $form;
	}

	public function klientSearchFormSucceeded(Nette\Application\UI\Form $form) {
			$this->redirect('Homepage:default', $form->getValues()->search, 1);
	}
}
