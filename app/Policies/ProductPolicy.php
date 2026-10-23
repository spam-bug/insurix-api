<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class ProductPolicy
{
    use ChecksMembership;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return $this->isMember($user, $product->workspace_id);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->isMember($user, $product->workspace_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->isMember($user, $product->workspace_id);
    }
}
