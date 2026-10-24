<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Products\ProductData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Workspace $workspace, ProductData $data): Product
    {
        $product = DB::transaction(fn () => $workspace->products()->create($data->toArray()));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Products, ChangeAction::Created, $product->id, $product->name));

        return $product;
    }
}
