<div
    x-data
    x-on:toast-success.window="SwalHelper.toastSuccess($event.detail.message);"
    x-on:swal-error.window="SwalHelper.error($event.detail.message, $event.detail.title);"
    x-on:confirm-delete-source.window="
        (async () => {
            const confirmed = await SwalHelper.confirm({
                title: 'Confirm Source Deletion',
                text: 'This action is irreversible',
                confirmText: 'Yes, Delete'
            });

            if (confirmed) {
                $wire.destroy($event.detail.sourceId);
            }
        })();
    "
>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                    <div>
                        <h5 class="card-title mb-0"><i class="bx bx-git-branch me-2 text-primary"></i>Revenue Sources</h5>
                        <small class="text-muted">Manage the General Fund receipts hierarchy.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div style="min-width: 260px;">
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search name, code, type..."
                            >
                        </div>
                        <button
                            class="btn btn-primary btn-sm px-3"
                            type="button"
                            wire:click="createSource"
                            wire:loading.attr="disabled"
                            wire:target="createSource"
                        >
                            <i class="fas fa-plus me-1"></i> Add Source
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Source</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $source = $row['source'];
                                        $depth = $row['depth'];
                                        $badgeClass = match ($source->source_type) {
                                            \App\Models\RevenueSource::TYPE_MAIN_SOURCE => 'primary',
                                            \App\Models\RevenueSource::TYPE_CATEGORY => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr wire:key="revenue-source-{{ $source->id }}">
                                        <td>
                                            <div class="d-flex align-items-center" style="padding-left: {{ $depth * 1.4 }}rem;">
                                                @if ($depth > 0)
                                                    <i class="bx bx-subdirectory-right text-muted me-1"></i>
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $source->name }}</div>
                                                    @if ($source->display_code && $source->source_type !== \App\Models\RevenueSource::TYPE_LINE_ITEM)
                                                        <small class="text-muted">Display: {{ $source->display_code }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td><code>{{ $source->code }}</code></td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $badgeClass }} bg-soft text-{{ $badgeClass }}">
                                                {{ str($source->source_type)->replace('_', ' ')->title() }}
                                            </span>
                                            @if ($source->accepts_values)
                                                <span class="badge rounded-pill bg-success bg-soft text-success">Accepts Values</span>
                                            @endif
                                        </td>
                                        <td>{{ $source->sort_order }}</td>
                                        <td>
                                            @if ($source->is_enabled)
                                                <span class="badge rounded-pill bg-success bg-soft text-success">Enabled</span>
                                            @else
                                                <span class="badge rounded-pill bg-danger bg-soft text-danger">Disabled</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Revenue source actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary"
                                                    wire:click="editSource({{ $source->id }})"
                                                    wire:loading.attr="disabled"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger"
                                                    x-on:click="$dispatch('confirm-delete-source', { sourceId: {{ $source->id }} })"
                                                    wire:loading.attr="disabled"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No revenue sources matched your search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">{{ $editingId ? 'Edit Source' : 'Add Source' }}</h5>
                        <small class="text-muted">Hierarchy levels are validated on save.</small>
                    </div>
                    @if ($editingId)
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="createSource">
                            New
                        </button>
                    @endif
                </div>

                <div class="card-body">
                    <form wire:submit="{{ $editingId ? 'update' : 'store' }}">
                        <div class="mb-3">
                            <label for="sourceType" class="form-label fw-semibold">Source Type</label>
                            <select
                                id="sourceType"
                                class="form-select @error('sourceType') is-invalid @enderror"
                                wire:model.live="sourceType"
                            >
                                @foreach ($sourceTypes as $type)
                                    <option value="{{ $type->lookup_code }}">{{ $type->meaning }}</option>
                                @endforeach
                            </select>
                            @error('sourceType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parentId" class="form-label fw-semibold">Parent Source</label>
                            <select
                                id="parentId"
                                class="form-select @error('parentId') is-invalid @enderror"
                                wire:model.live="parentId"
                            >
                                <option value="">No parent</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}">
                                        {{ str($option->source_type)->replace('_', ' ')->title() }} - {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parentId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label fw-semibold">Code</label>
                            <input
                                type="text"
                                id="code"
                                class="form-control @error('code') is-invalid @enderror"
                                wire:model.live.blur="code"
                                placeholder="e.g. community_tax"
                                maxlength="100"
                            >
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if ($sourceType !== \App\Models\RevenueSource::TYPE_LINE_ITEM)
                            <div class="mb-3">
                                <label for="displayCode" class="form-label fw-semibold">Display Code</label>
                                <input
                                    type="text"
                                    id="displayCode"
                                    class="form-control @error('displayCode') is-invalid @enderror"
                                    wire:model.live.blur="displayCode"
                                    placeholder="e.g. 1"
                                    maxlength="20"
                                >
                                @error('displayCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Name</label>
                            <input
                                type="text"
                                id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                wire:model.live.blur="name"
                                maxlength="255"
                            >
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sortOrder" class="form-label fw-semibold">Sort Order</label>
                            <input
                                type="number"
                                id="sortOrder"
                                class="form-control @error('sortOrder') is-invalid @enderror"
                                wire:model.live.blur="sortOrder"
                                min="0"
                                max="65535"
                            >
                            @error('sortOrder') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-semibold">Remarks</label>
                            <textarea
                                id="remarks"
                                class="form-control @error('remarks') is-invalid @enderror"
                                wire:model.live.blur="remarks"
                                rows="3"
                            ></textarea>
                            @error('remarks') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check mb-2">
                            <input
                                type="checkbox"
                                id="acceptsValues"
                                class="form-check-input @error('acceptsValues') is-invalid @enderror"
                                wire:model.live="acceptsValues"
                            >
                            <label class="form-check-label" for="acceptsValues">Accepts Values</label>
                            @error('acceptsValues') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input
                                type="checkbox"
                                id="isEnabled"
                                class="form-check-input @error('isEnabled') is-invalid @enderror"
                                wire:model.live="isEnabled"
                            >
                            <label class="form-check-label" for="isEnabled">Is Enabled</label>
                            @error('isEnabled') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            @if ($editingId)
                                <button type="button" class="btn btn-secondary w-100" wire:click="createSource">Cancel</button>
                            @endif
                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                                wire:loading.attr="disabled"
                                wire:target="{{ $editingId ? 'update' : 'store' }}"
                            >
                                <span wire:loading wire:target="{{ $editingId ? 'update' : 'store' }}" class="spinner-border spinner-border-sm me-1"></span>
                                {{ $editingId ? 'Update Source' : 'Create Source' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
