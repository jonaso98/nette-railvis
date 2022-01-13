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
    
    public function renderDefault(int $page = 1): void
    {
        $postsCount = $this->database
            ->table('posts')
            ->count();
        
		// We'll make the Paginator instance and set it up
		$paginator = new Nette\Utils\Paginator;
		$paginator->setItemCount($postsCount); // total articles count
		$paginator->setItemsPerPage(5); // items per page
		$paginator->setPage($page); // actual page number

        $this->template->posts = $this->database
            ->query('select 
                        p.id, 
                        p.id_category, 
                        p.content, 
                        p.created_at,
                        category.name as category_name,
                        region.name as region_name
                    from posts as p
                    left join category on category.id = p.id_category
                    left join region ON region.id = p.id_region
                    WHERE p.created_at < ?
                    ORDER BY p.created_at DESC
                    LIMIT ?
                    OFFSET ?
                    ', new \DateTime, $paginator->getLength(), $paginator->getOffset());
        $this->template->paginator = $paginator;
    }

    public function renderShow(int $postId): void
	{
		$this->template->post = $this->database
			->table('posts')
			->get($postId);
	}
}
