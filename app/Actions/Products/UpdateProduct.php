<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Products\ProductData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Changes the product for future policies. Policies already recorded keep the product
     * name and commission rate they were saved with.
     */
    public function handle(Product $product, ProductData $data): Product
    {
        DB::transaction(fn () => $product->update($data->toArray()));

        $this->announceWorkspaceChange->handle($product->workspace_id, new WorkspaceChangeData(ChangeTopic::Products, ChangeAction::Updated, $product->id, $product->name));

        return $product;
    }
}
