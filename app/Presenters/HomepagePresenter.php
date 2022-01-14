<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Application\UI\Forms\Controls,
	Tracy\Debugger,
	Tracy\Dumper;

Debugger::enable();


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
		$paginator->setItemsPerPage(10); // items per page
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
    protected function createComponentNewPostForm(): Form
	{
		$categories = $this->database
        ->fetchPairs('SELECT ca.* FROM category AS ca');

        $regions = $this->database
        ->fetchPairs('SELECT re.* FROM region AS re');
        
        $form = new Form;
        $form->addSelect('category', 'Category', $categories)->setHtmlAttribute('class', 'form-control');
        $form->addTextArea('content', 'Content')->setRequired(TRUE)
        ->setHtmlAttribute('class', 'form-control');
        $form->addSelect('region', 'Region', $regions)->setPrompt('Select One')->setHtmlAttribute('class', 'form-control');
        $form->addText('tags', 'Tags')->setHtmlAttribute('data-role', 'tagsinput')->setHtmlAttribute('class', 'form-control');
        $form->addSubmit('submit', 'Send');
        
        // setup form rendering
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = NULL;
        $renderer->wrappers['pair']['container'] = 'div class=control-group';
        $renderer->wrappers['pair']['.error'] = 'error';
        $renderer->wrappers['control']['container'] = 'div class=controls';
        $renderer->wrappers['label']['container'] = 'div class=control-label';
        $renderer->wrappers['control']['description'] = 'span class=help-inline';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-inline';

        // make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form');
        $form->onSuccess[] = [$this, 'formSucceeded'];
		return $form;
	}

	public function formSucceeded(Form $form, $data): void
	{
        
		// here we will process the data sent by the form
        $post = $this->database
		->query('INSERT INTO posts', [
            [
                'id_category' => $data->category,
                'content' => $data->content,
                'id_region' => $data->region ? $data->region : null,
            ]
        ]);
        $id_post = $this->database->getInsertId();

        $array = explode(",", $data->tags);
    
        foreach ($array as $tag) {
            $tags = $this->database
		    ->query('INSERT INTO tags', [
                [ 'title' => $tag] 
            ]);
            $id_tag = $this->database->getInsertId();

            $post_tags = $this->database
		    ->query('INSERT INTO posts_tags', [
            [   
                'id_post' => $id_post,
                'id_tag' => $id_tag]
            ]);
        }		
        $this->flashMessage('You have successfully save a post.');
		$this->redirect('Homepage:');
	}
}
