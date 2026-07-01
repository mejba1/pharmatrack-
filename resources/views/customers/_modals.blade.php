{{-- ─────────────────────── Add Customer ─────────────────────── --}}
<div class="modal fade" :class="{show:showAdd}" :style="showAdd?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('customers.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2 text-primary"></i>Add Customer</h5><button type="button" class="btn-close" @click="showAdd=false"></button></div>
        <div class="modal-body">@include('customers._form')</div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" @click="showAdd=false">Cancel</button><button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Customer</button></div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showAdd" @click="showAdd=false" x-cloak></div>

{{-- ─────────────────────── Edit Customer ─────────────────────── --}}
<div class="modal fade" :class="{show:showEdit}" :style="showEdit?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" :action="editAction" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil me-2 text-primary"></i>Edit Customer</h5><button type="button" class="btn-close" @click="showEdit=false"></button></div>
        <div class="modal-body">@include('customers._form')</div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-sm" @click="showEdit=false">Cancel</button><button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Update</button></div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showEdit" @click="showEdit=false" x-cloak></div>

{{-- ─────────────────────── View Customer ─────────────────────── --}}
<div class="modal fade" :class="{show:showView}" :style="showView?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-badge me-2 text-primary"></i><span x-text="view?.name ?? 'Loading…'"></span>
          <span class="badge bg-primary-subtle text-primary ms-2 font-monospace" x-show="view?.customer_code" x-text="view?.customer_code"></span>
        </h5>
        <button type="button" class="btn-close" @click="showView=false"></button>
      </div>
      <div class="modal-body" x-show="view && !view.error">
        <div class="row g-3 mb-3">
          <div class="col-md-6"><div class="perm-box">
            <div class="d-flex align-items-center gap-2 mb-2">
              <template x-if="view?.logo_url"><img :src="view.logo_url" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover"></template>
              <div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Profile</div>
            </div>
            <table class="table table-sm mb-0">
              <tr><td class="text-muted" style="width:140px">Type</td><td><span class="badge bg-primary-subtle text-primary" x-text="view?.type_label"></span></td></tr>
              <tr><td class="text-muted">Email</td><td x-text="view?.email ?? '—'"></td></tr>
              <tr><td class="text-muted">Phone</td><td x-text="view?.phone ?? '—'"></td></tr>
              <tr><td class="text-muted">Country / City</td><td><span x-text="view?.country_name ?? '—'"></span><span x-show="view?.city" x-text="', ' + (view?.city||'')"></span></td></tr>
              <tr><td class="text-muted">Address</td><td x-text="view?.address ?? '—'"></td></tr>
              <tr><td class="text-muted">Company</td><td x-text="view?.company_name ?? '—'"></td></tr>
              <tr><td class="text-muted">Company ID</td><td x-text="view?.company_id ?? '—'"></td></tr>
              <tr><td class="text-muted">Identification</td><td><span x-text="view?.id_type_label || '—'"></span><span x-show="view?.identification_number" x-text="' · ' + (view?.identification_number||'')"></span></td></tr>
              <tr><td class="text-muted">Referenced by</td><td x-text="view?.referenced_by ?? '—'"></td></tr>
              <tr><td class="text-muted">Account Manager</td><td x-text="view?.manager_name ?? '—'"></td></tr>
              <tr><td class="text-muted">Portal login</td><td><span x-show="view?.has_login" class="badge bg-success-subtle text-success">Enabled</span><span x-show="!view?.has_login" class="text-muted">Not set</span></td></tr>
            </table>
          </div></div>
          <div class="col-md-6"><div class="perm-box">
            <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Sales history</div>
            <div style="max-height:240px;overflow:auto" class="no-sb">
              <table class="table table-sm mb-0">
                <thead><tr><th>Ref</th><th>Date</th><th class="text-center">Units</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                  <template x-for="s in (view?.sales||[])" :key="s.id">
                    <tr><td class="font-monospace small" x-text="s.reference"></td><td class="small" x-text="s.date"></td><td class="text-center" x-text="s.units"></td><td class="text-end small" x-text="s.currency+' '+s.total"></td></tr>
                  </template>
                  <tr x-show="!(view?.sales||[]).length"><td colspan="4" class="text-center text-muted py-3">No sales yet.</td></tr>
                </tbody>
              </table>
            </div>
          </div></div>
        </div>
        {{-- Shared documents (staff pick what the customer sees in their portal) --}}
        <div class="perm-box mb-3">
          <div class="d-flex align-items-center mb-2">
            <div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Shared documents — <span x-text="(view?.documents||[]).length"></span></div>
            <span class="ms-auto text-muted-sm"><i class="bi bi-eye me-1"></i>Visible in the customer's portal</span>
          </div>
          @can('customers.create')
          <form method="POST" :action="'{{ url('customers') }}/' + (view?.id) + '/documents'" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-4"><input type="text" name="name" class="form-control form-control-sm" placeholder="Document name (optional)"></div>
            <div class="col-md-3"><input type="text" name="category" class="form-control form-control-sm" placeholder="Category (optional)"></div>
            <div class="col-md-3"><input type="file" name="file" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload me-1"></i>Share</button></div>
          </form>
          @endcan
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <tbody>
                <template x-for="d in (view?.documents||[])" :key="d.id">
                  <tr>
                    <td><i class="bi bi-file-earmark-text me-1" :class="d.icon"></i><a :href="d.url" target="_blank" x-text="d.name"></a></td>
                    <td class="small text-muted" x-text="d.category || '—'"></td>
                    <td class="small text-muted" x-text="d.size + ' · ' + d.date"></td>
                    <td class="text-end">
                      @can('customers.delete')
                      <form method="POST" :action="d.delete_url" @submit="return confirm('Remove this shared document?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button>
                      </form>
                      @endcan
                    </td>
                  </tr>
                </template>
                <tr x-show="!(view?.documents||[]).length"><td colspan="4" class="text-center text-muted py-2">No documents shared yet.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="perm-box">
          <div class="text-muted-sm text-uppercase fw-bold mb-2" style="font-size:11px">Units purchased (traceable) — <span x-text="(view?.units||[]).length"></span></div>
          <div style="max-height:260px;overflow:auto" class="no-sb">
            <table class="table table-sm mb-0">
              <thead><tr><th>UUC code</th><th>Serial</th><th>Product</th><th>Batch</th><th>Sold</th></tr></thead>
              <tbody>
                <template x-for="u in (view?.units||[])" :key="u.code">
                  <tr><td class="font-monospace small" x-text="u.code"></td><td class="small">#<span x-text="u.serial"></span></td><td class="small" x-text="u.product"></td><td class="small" x-text="u.batch"></td><td class="small" x-text="u.sold_at"></td></tr>
                </template>
                <tr x-show="!(view?.units||[]).length"><td colspan="5" class="text-center text-muted py-3">No units assigned yet.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-body text-center text-muted py-5" x-show="!view"><div class="spinner-border text-primary"></div></div>
      <div class="modal-footer">
        <form method="POST" :action="editAction" x-show="false"></form>
        <button type="button" class="btn btn-outline-success btn-sm" @click="showView=false; openSale(view?.id)" x-show="view && !view.error"><i class="bi bi-receipt me-1"></i>Record sale</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="showView=false">Close</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showView" @click="showView=false" x-cloak></div>

{{-- ─────────────────────── Record Sale ─────────────────────── --}}
<div class="modal fade" :class="{show:showSale}" :style="showSale?'display:block':''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('customers.sales.store') }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt me-2 text-primary"></i>Record Sale</h5><button type="button" class="btn-close" @click="showSale=false"></button></div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label">Customer <span class="text-danger">*</span></label>
              <select name="customer_id" class="form-select form-select-sm" x-model="sale.customer_id" required>
                <option value="">Select customer…</option>
                @foreach($allCustomers as $c)
                  <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->customer_code }})</option>
                @endforeach
              </select>
              <div class="text-muted-sm mt-1">Tip: open a customer row's <i class="bi bi-receipt"></i> to preselect.</div>
            </div>
            <div class="col-md-3"><label class="form-label">Sale date <span class="text-danger">*</span></label><input type="date" name="sale_date" class="form-control form-control-sm" x-model="sale.sale_date" required></div>
            <div class="col-md-2"><label class="form-label">Currency</label><input type="text" name="currency" maxlength="3" class="form-control form-control-sm text-uppercase" x-model="sale.currency"></div>
            <div class="col-md-2"><label class="form-label">Status</label>
              <select name="status" class="form-select form-select-sm" x-model="sale.status">
                <option value="confirmed">Confirmed</option><option value="delivered">Delivered</option><option value="draft">Draft</option>
              </select>
            </div>
          </div>

          <div class="d-flex align-items-center mb-2">
            <div class="text-muted-sm text-uppercase fw-bold" style="font-size:11px">Line items</div>
            <button type="button" class="btn btn-outline-primary btn-sm ms-auto" @click="addItem()"><i class="bi bi-plus-lg me-1"></i>Add line</button>
          </div>

          <template x-for="(it, i) in sale.items" :key="i">
            <div class="perm-box mb-2">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Product <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" :name="`items[${i}][product_id]`" x-model="it.product_id" @change="onItemProduct(it)" required>
                    <option value="">Select…</option>
                    @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->prn }})</option>@endforeach
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Batch</label>
                  <select class="form-select form-select-sm" :name="`items[${i}][batch_id]`" x-model="it.batch_id">
                    <option value="">— any / unassigned —</option>
                    <template x-for="b in batchesFor(it.product_id)" :key="b.id"><option :value="b.id" x-text="b.label"></option></template>
                  </select>
                </div>
                <div class="col-md-2"><label class="form-label">Qty <span class="text-danger">*</span></label><input type="number" min="1" class="form-control form-control-sm" :name="`items[${i}][quantity]`" x-model="it.quantity" required></div>
                <div class="col-md-2"><label class="form-label">Unit price</label><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`items[${i}][unit_price]`" x-model="it.unit_price" placeholder="0.00"></div>
                <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm btn-icon" @click="removeItem(i)"><i class="bi bi-trash"></i></button></div>
                <div class="col-12">
                  <label class="form-label">Specific UUC codes <span class="text-muted-sm">(optional — leave blank to auto-assign Qty available units from the batch)</span></label>
                  <textarea rows="1" class="form-control form-control-sm" :name="`items[${i}][uuc_codes]`" x-model="it.uuc_codes" placeholder="HAK33NQJCP, QHFMC4PAUD … (comma / space / newline)"></textarea>
                </div>
              </div>
            </div>
          </template>

          <div class="d-flex justify-content-end mt-2">
            <div class="text-end"><div class="text-muted-sm">Estimated total</div><div class="fw-bold fs-5"><span x-text="sale.currency"></span> <span x-text="saleTotal"></span></div></div>
          </div>
          <textarea name="notes" rows="2" class="form-control form-control-sm mt-2" x-model="sale.notes" placeholder="Notes (optional)"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="showSale=false">Cancel</button>
          <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Record Sale &amp; assign units</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showSale" @click="showSale=false" x-cloak></div>
