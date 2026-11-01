<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Portal\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Portal\Resources\ListingResource\Pages\EditListing;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\Listing;
use App\Models\Project;
use App\Models\User;
use App\Services\TurkeyAddressService as A;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role'=>UserRole::SUPERVISOR]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_real_create_forms_load_every_child_and_clear_changed_parents(): void
    {
        foreach ([[CreateListing::class,'_id'],[CreateProject::class,''],[CreateUser::class,'']] as [$class,$suffix]) {
            $page=Livewire::test($class);
            if ($class === CreateUser::class) $page->set('data.role',UserRole::INDIVIDUAL->value);
            $cities=$page->instance()->getFormSelectOptions('data.city'.$suffix);
            $this->assertSame(array_values(A::getCities()),array_column($cities,'value'));
            $page->set('data.city'.$suffix,'Artvin')->set('data.district'.$suffix,'Yusufeli');
            $options=$page->instance()->getFormSelectOptions('data.neighborhood'.$suffix);
            $this->assertSame(array_values(A::getNeighborhoods('Artvin','Yusufeli')),array_column($options,'value'));
            $last=end($options)['value'];
            $page->set('data.neighborhood'.$suffix,$last)->set('data.district'.$suffix,'Arhavi')->assertSet('data.neighborhood'.$suffix,null);
            $page->set('data.district'.$suffix,'Yusufeli')->set('data.neighborhood'.$suffix,$last)->set('data.city'.$suffix,'Ankara')
                ->assertSet('data.district'.$suffix,null)->assertSet('data.neighborhood'.$suffix,null);
        }
    }

    public function test_saved_draft_reopens_and_address_edit_saves_without_publication(): void
    {
        $names=A::getNeighborhoods('Artvin','Yusufeli');$last=end($names);
        $listing=Listing::create(['user_id'=>auth()->id(),'title'=>'Adres testi','slug'=>'adres-testi','description'=>'Adres kaydı testi','category'=>'Daire','type'=>'Satılık','price'=>100,'currency'=>'TRY','square_meters'=>100,'net_square_meters'=>80,'eids_document_no'=>'12345','city_id'=>'Artvin','district_id'=>'Yusufeli','neighborhood_id'=>$last,'is_active'=>false,'publication_status'=>'draft']);
        $page=Livewire::test(EditListing::class,['record'=>$listing->id])->assertSet('data.city_id','Artvin')->assertSet('data.district_id','Yusufeli')->assertSet('data.neighborhood_id',$last);
        $new=A::getNeighborhoods('Kastamonu','Merkez');$newLast=end($new);
        $page->set('data.city_id','Kastamonu')->set('data.district_id','Merkez')->set('data.neighborhood_id',$newLast)->call('save')->assertHasNoFormErrors();
        $listing->refresh();$this->assertSame('Kastamonu',$listing->city_id);$this->assertSame($newLast,$listing->neighborhood_id);$this->assertFalse($listing->is_active);
        Livewire::test(EditListing::class,['record'=>$listing->id])->assertSet('data.neighborhood_id',$newLast);
    }

    public function test_project_reopens_and_address_edit_persists(): void
    {
        $names=A::getNeighborhoods('Artvin','Yusufeli');$last=end($names);
        $project=Project::create(['title'=>'Adres testi','slug'=>'adres-testi','developer_name'=>'Test','status'=>'planning','city'=>'Artvin','district'=>'Yusufeli','neighborhood'=>$last,'latitude'=>41,'longitude'=>41]);
        $page=Livewire::test(EditProject::class,['record'=>$project->id])->assertSet('data.neighborhood',$last);
        $new=A::getNeighborhoods('Kastamonu','Merkez');$newLast=end($new);
        $page->set('data.city','Kastamonu')->set('data.district','Merkez')->set('data.neighborhood',$newLast)->call('save')->assertHasNoFormErrors();
        $this->assertSame($newLast,$project->fresh()->neighborhood);
        Livewire::test(EditProject::class,['record'=>$project->id])->assertSet('data.neighborhood',$newLast);
    }

    public function test_member_saved_address_reopens(): void
    {
        $names=A::getNeighborhoods('Artvin','Yusufeli');$last=end($names);
        $member=User::factory()->create(['role'=>UserRole::INDIVIDUAL,'city'=>'Artvin','district'=>'Yusufeli','neighborhood'=>$last]);
        Livewire::test(EditUser::class,['record'=>$member->id])->assertSet('data.city','Artvin')->assertSet('data.district','Yusufeli')->assertSet('data.neighborhood',$last);
    }

    public function test_legacy_project_labels_and_stored_values_are_preserved_on_save(): void
    {
        $project=Project::create(['title'=>'Eski adres','slug'=>'eski-adres','developer_name'=>'Test','status'=>'planning','city'=>'Osmaniye','district'=>'Osmaniye Merkez','neighborhood'=>'Fakıuşağı Mah.','latitude'=>37,'longitude'=>36]);
        $page=Livewire::test(EditProject::class,['record'=>$project->id])->assertSet('data.district','Osmaniye Merkez')->assertSet('data.neighborhood','Fakıuşağı Mah.');
        $this->assertSame('Fakıuşağı Mah.',$page->instance()->getFormSelectOptionLabel('data.neighborhood'));
        $this->assertSame('Osmaniye Merkez',$page->instance()->getFormSelectOptionLabel('data.district'));
        $options=$page->instance()->getFormSelectOptions('data.neighborhood');
        $this->assertSame(array_values(A::getNeighborhoods('Osmaniye','Merkez')),array_column($options,'value'));
        $page->set('data.title','Eski adres korundu')->call('save')->assertHasNoFormErrors();
        $this->assertSame('Osmaniye Merkez',$project->fresh()->district);
        $this->assertSame('Fakıuşağı Mah.',$project->fresh()->neighborhood);
    }

    public function test_location_endpoints_return_every_source_record_and_reject_mismatched_parent(): void
    {
        $this->getJson(route('locations.districts',['city'=>'İstanbul']))->assertOk()->assertExactJson(['data'=>A::getDistricts('İstanbul')]);
        foreach ([['Artvin','Yusufeli'],['Kastamonu','Merkez'],['Bartın','Merkez']] as [$city,$district]) {
            $this->getJson(route('locations.neighborhoods',compact('city','district')))->assertOk()->assertExactJson(['data'=>A::getNeighborhoods($city,$district)]);
        }
        $this->getJson(route('locations.neighborhoods',['city'=>'Ankara','district'=>'Kadıköy']))->assertUnprocessable();
    }
}
