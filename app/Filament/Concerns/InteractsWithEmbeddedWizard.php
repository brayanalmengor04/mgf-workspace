<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Group;
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
        return Group::make([
            EmbeddedSchema::make('form'),
            $this->getFormActionsContentComponent(),
        ]);
    }

    public function areFormActionsSticky(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        foreach ($schema->getComponents(withHidden: true) as $component) {
            if ($component instanceof Wizard) {
                $component
                    ->cancelAction(null)
                    ->submitAction(null)
                    ->persistStepInQueryString('step');
            }
        }

        return $schema;
    }
}
