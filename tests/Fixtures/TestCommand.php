<?php

namespace Tests\Fixtures;

use Illuminate\Console\Command;

class TestCommand extends Command
{
	public function handle(): int
	{
		return self::SUCCESS;
	}

	protected function configure(): void
	{
		$this->setName( 'artisanpack:test-command' );
		$this->setDescription( 'A fixture command used to test the base service provider.' );
	}
}
