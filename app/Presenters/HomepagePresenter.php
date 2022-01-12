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
    
    public function renderDefault(): void
    {
        $this->template->posts = $this->database
            ->query('select 
                        p.id, 
                        p.id_category, 
                        p.content, 
                        p.created_at,
                        category.name as category_name
                    from posts as p
                    left join category on category.id = p.id_category
                    ');
    }
}
