<?php

namespace App\Support;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;

/** Admin presentation only: retain native URLs, badges and authorization checks. */
final class AdminNavigation
{
    public static function build(NavigationBuilder $builder): NavigationBuilder
    {
        $panel = Filament::getCurrentPanel();
        $groups = array_fill_keys(['Yönetim', 'Üyelik', 'İçerik', 'Sistem'], []);

        foreach ([...$panel->getPages(), ...$panel->getResources()] as $source) {
            // These are the same checks used by native Filament navigation registration.
            if (filled($source::getCluster()) || ! $source::shouldRegisterNavigation() || ! $source::canAccess()) {
                continue;
            }

            $group = match (class_basename($source)) {
                'Dashboard', 'ListingResource', 'ProjectResource', 'MobileDraftResource',
                'MobileImportResource', 'MobileSubmissionResource' => 'Yönetim',
                'UserResource', 'BusinessProfileResource', 'AgentConsultantResource', 'ConsultantResource' => 'Üyelik',
                'PostResource', 'CategoryResource', 'AdvertisementResource', 'PollResource', 'LegalDocumentResource' => 'İçerik',
                'ManageSettings', 'ErrorLogResource', 'ListingChangeLogResource' => 'Sistem',
                default => $source::getNavigationGroup() ?: 'Yönetim',
            };

            foreach ($source::getNavigationItems() as $item) {
                $groups[$group][] = $item;
            }
        }

        foreach ($panel->getNavigationItems() as $item) {
            $groups[$item->getGroup() ?: 'Yönetim'][] = $item;
        }

        $navigation = [];
        foreach ($groups as $label => $items) {
            $items = collect($items)->filter(fn ($item) => $item->isVisible())
                ->sortBy(fn ($item) => $item->getSort());
            // Match native duplicate-label and nested-item handling as well.
            $parents = $items->groupBy(fn ($item) => $item->getParentItem() ?: '');
            $items = $parents->get('', collect())->keyBy(fn ($item) => $item->getLabel());
            foreach ($parents->except(['']) as $parent => $children) {
                if ($items->has($parent)) {
                    $items->get($parent)->childItems($children);
                }
            }
            $items = $items->filter(fn ($item) => filled($item->getChildItems()) || filled($item->getUrl()));
            if ($items->isNotEmpty()) {
                $navigation[] = NavigationGroup::make($label)->items($items->values()->all());
            }
        }

        return $builder->groups($navigation);
    }
}
