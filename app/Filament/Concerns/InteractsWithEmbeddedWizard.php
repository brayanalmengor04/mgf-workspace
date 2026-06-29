<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

trait InteractsWithEmbeddedWizard
{
    public function hasFormWrapper(): bool
    {
        return false;
    }

    public function getFormContentComponent(): Component
    {
        return EmbeddedSchema::make('form');
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        foreach ($schema->getComponents(withHidden: true) as $component) {
            if ($component instanceof Wizard) {
                $component
                    ->cancelAction($this->getCancelFormAction())
                    ->submitAction($this->getSubmitFormAction())
                    ->alpineSubmitHandler("\$wire.{$this->getSubmitFormLivewireMethodName()}()");
            }
        }

        return $schema;
    }
}
