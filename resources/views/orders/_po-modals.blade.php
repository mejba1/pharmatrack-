{{-- ═══════════════ View PO Modal ═══════════════ --}}
<div class="modal fade" :class="{show: showViewModal}" :style="showViewModal ? 'display:block' : ''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" x-show="selectedPO">
      <div class="modal-header">
        <div><h5 class="modal-title fw-semibold" x-text="selectedPO?.id"></h5><div class="text-muted-sm">Purchase Order</div></div>
        <div class="ms-auto d-flex gap-2 align-items-center">
          <span class="badge-status" :class="'badge-' + selectedPO?.statusClass" x-text="selectedPO?.status"></span>
          <button class="btn-close ms-2" @click="showViewModal=false"></button>
        </div>
      </div>
      <div class="modal-doc-tabs">
        <button :class="{active: viewTab==='details'}" @click="viewTab='details'"><i class="bi bi-file-text"></i>Order Details</button>
        <button :class="{active: viewTab==='docs'}" @click="viewTab='docs'"><i class="bi bi-paperclip"></i>Reference Documents
          <span class="badge bg-primary rounded-pill py-0 px-1" style="font-size:10px" x-show="(selectedPO?.docs||[]).length>0" x-text="(selectedPO?.docs||[]).length"></span>
        </button>
      </div>

      {{-- Details --}}
      <div class="modal-body" x-show="viewTab==='details'">
        {{-- Document chain progress: PO → SO → PI → CI --}}
        <div class="p-3 border rounded-3 mb-3 bg-light">
          <div class="text-muted-sm mb-2 fw-semibold">ORDER PROGRESS</div>
          <div class="d-flex align-items-center">
            <template x-for="(s, i) in [{n:1,l:'PO'},{n:2,l:'SO'},{n:3,l:'PI'},{n:4,l:'CI'}]" :key="s.n">
              <div class="d-flex align-items-center" :class="i<3 && 'flex-fill'">
                <div class="text-center" style="width:46px">
                  <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                       :style="((selectedPO?.chain?.step||1) >= s.n) ? 'width:34px;height:34px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff' : 'width:34px;height:34px;background:#fff;border:1px solid #dee2e6;color:#adb5bd'">
                    <template x-if="(selectedPO?.chain?.step||1) >= s.n"><i class="bi bi-check-lg"></i></template>
                    <template x-if="(selectedPO?.chain?.step||1) < s.n"><span style="font-size:11px;font-weight:700" x-text="s.l"></span></template>
                  </div>
                  <div class="mt-1" style="font-size:11px" :class="((selectedPO?.chain?.step||1) >= s.n) ? 'fw-semibold text-success' : 'text-muted'">
                    <span x-text="s.l"></span><span x-show="s.n===4 && (selectedPO?.chain?.ci_count||0) > 1" x-text="' ×'+selectedPO?.chain?.ci_count"></span>
                  </div>
                </div>
                <div class="flex-fill mx-1" x-show="i<3" style="height:3px;border-radius:2px"
                     :style="((selectedPO?.chain?.step||1) > s.n) ? 'background:#16a34a' : 'background:#dee2e6'"></div>
              </div>
            </template>
          </div>
          <div class="text-muted-sm mt-2" x-show="(selectedPO?.chain?.step||1) < 4">
            Next step: <span class="fw-semibold" x-text="({1:'Acknowledge, then create Sales Order',2:'Issue Proforma Invoice',3:'Raise Commercial Invoice'})[selectedPO?.chain?.step] || ''"></span>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-2 fw-semibold">BUYER</div><div class="fw-semibold" x-text="selectedPO?.buyer"></div><div class="text-muted-sm" x-text="selectedPO?.country"></div></div></div>
          <div class="col-md-6"><div class="p-3 border rounded-3"><div class="text-muted-sm mb-2 fw-semibold">MANAGER</div><div class="fw-semibold" x-text="selectedPO?.manager || '—'"></div><div class="text-muted-sm">Payment: <span x-text="selectedPO?.payTerms"></span> <span x-show="selectedPO?.incoterms">· <span x-text="selectedPO?.incoterms"></span></span></div></div></div>
          <div class="col-md-4"><label class="form-label">PO Date</label><div x-text="selectedPO?.poDate"></div></div>
          <div class="col-md-4"><label class="form-label">Required Delivery</label><div x-text="selectedPO?.requiredBy"></div></div>
          <div class="col-md-4"><label class="form-label">Total Value</label><div class="fw-bold text-primary" x-text="selectedPO?.value"></div></div>
          <div class="col-12">
            <label class="form-label fw-semibold">Order Lines</label>
            <table class="table table-sm border rounded-3 overflow-hidden">
              <thead class="table-light"><tr><th>#</th><th>Product (PRN)</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
              <tbody>
                <template x-for="(line,i) in (selectedPO?.lines||[])" :key="i">
                  <tr><td x-text="i+1"></td><td><div class="fw-semibold" style="font-size:13px" x-text="line.product"></div><div class="text-muted-sm" x-text="line.prn"></div></td>
                    <td x-text="Number(line.qty).toLocaleString()"></td><td x-text="line.unitPrice"></td><td class="fw-semibold" x-text="line.total"></td></tr>
                </template>
                <tr class="table-light"><td colspan="4" class="text-end fw-semibold">Grand Total</td><td class="fw-bold text-primary" x-text="selectedPO?.value"></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Docs --}}
      <div class="modal-body" x-show="viewTab==='docs'">
        <form method="POST" :action="docUrl()" enctype="multipart/form-data" class="d-flex gap-2 align-items-end mb-3">
          @csrf
          <div class="flex-grow-1"><label class="form-label">Upload reference document</label><input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.webp" required></div>
          <div style="width:200px"><label class="form-label">Category</label><input type="text" name="category" class="form-control form-control-sm" placeholder="Signed PO, COA…"></div>
          <button class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i>Upload</button>
        </form>
        <div x-show="!(selectedPO?.docs||[]).length" class="text-center py-5"><i class="bi bi-folder2-open" style="font-size:40px;opacity:.3;display:block;margin-bottom:10px"></i><div class="text-muted-sm">No reference documents attached yet.</div></div>
        <div class="d-flex flex-column gap-2">
          <template x-for="doc in (selectedPO?.docs||[])" :key="doc.id">
            <div class="doc-item">
              <div class="doc-type-icon" :class="doc.iconClass"><i class="bi bi-file-earmark"></i></div>
              <div class="flex-grow-1" style="min-width:0">
                <div class="fw-semibold text-truncate" style="font-size:13px" x-text="doc.name"></div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                  <span class="badge bg-light text-secondary border" style="font-size:10px" x-text="doc.category"></span>
                  <span class="text-muted-sm" x-text="doc.size"></span>
                  <span class="text-muted-sm">· <span x-text="doc.uploadedBy"></span></span>
                  <span class="text-muted-sm">· <span x-text="doc.date"></span></span>
                </div>
              </div>
              <div class="d-flex gap-1 flex-shrink-0">
                <a :href="doc.url" class="btn btn-outline-secondary btn-sm btn-icon"><i class="bi bi-download"></i></a>
                <form method="POST" :action="doc.del" @submit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm btn-icon"><i class="bi bi-trash"></i></button></form>
              </div>
            </div>
          </template>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" @click="showViewModal=false">Close</button>
        <a :href="pdfUrl(selectedPO)" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>PDF</a>
        {{-- Cancel --}}
        <form method="POST" :action="statusUrl()" x-show="selectedPO?.status!=='Cancelled' && selectedPO?.status!=='Acknowledged'" @submit="return confirm('Cancel this PO?')">
          @csrf <input type="hidden" name="action" value="cancel">
          <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Cancel PO</button>
        </form>
        {{-- Acknowledge --}}
        <form method="POST" :action="statusUrl()" x-show="selectedPO?.status==='Sent' || selectedPO?.status==='Draft'">
          @csrf <input type="hidden" name="action" value="acknowledge">
          <button class="btn btn-success btn-sm"><i class="bi bi-check2 me-1"></i>Acknowledge PO</button>
        </form>
        {{-- Create SO from this PO (preselects it on the SO page) --}}
        <a :href="'{{ route('orders.so') }}?po=' + selectedPO?.pid" class="btn btn-primary btn-sm" x-show="selectedPO?.status==='Acknowledged'"><i class="bi bi-arrow-right-circle me-1"></i>Create Sales Order</a>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showViewModal" @click="showViewModal=false" x-cloak></div>

{{-- ═══════════════ Create PO Modal ═══════════════ --}}
<div class="modal fade" :class="{show: showAddModal}" :style="showAddModal ? 'display:block' : ''" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.po.store') }}" x-ref="poForm">
        @csrf
        <input type="hidden" name="status" :value="form.status">
        <div class="modal-header"><h5 class="modal-title fw-semibold"><i class="bi bi-cart3 me-2 text-primary"></i>Create Purchase Order</h5><button type="button" class="btn-close" @click="showAddModal=false"></button></div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Customer type <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" x-model="form.buyer_type" @change="onType()">
                <option value="">All types</option>
                @foreach($types as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
              </select>
            </div>
            <div class="col-md-9">
              <label class="form-label">Buyer / Distributor <span class="text-danger">*</span></label>
              <select name="buyer_id" class="form-select form-select-sm" x-model="form.buyer_id" required>
                <option value="">Select customer…</option>
                <template x-for="c in filteredCustomers" :key="c.id"><option :value="c.id" x-text="c.name + (c.code ? ' (' + c.code + ')' : '') + (c.country ? ' — ' + c.country : '')"></option></template>
              </select>
              <div class="text-muted-sm mt-1" x-show="!filteredCustomers.length"><i class="bi bi-exclamation-circle me-1"></i>No customers assigned to you yet.</div>
            </div>

            <div class="col-md-3"><label class="form-label">PO Date</label><input type="date" name="po_date" class="form-control form-control-sm" x-model="form.po_date"></div>
            <div class="col-md-3"><label class="form-label">Required Delivery <span class="text-danger">*</span></label><input type="date" name="required_by_date" class="form-control form-control-sm" x-model="form.required_by_date" required></div>
            <div class="col-md-3">
              <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
              <select name="payment_terms" class="form-select form-select-sm" x-model="form.payment_terms" required>
                <option>30 days net</option><option>60 days net</option><option>Letter of Credit</option><option>Cash in advance</option><option>TT in advance</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Currency</label>
              <select name="currency" class="form-select form-select-sm" x-model="form.currency"><option>USD</option><option>EUR</option><option>GBP</option><option>BDT</option></select>
            </div>
            <div class="col-md-3"><label class="form-label">Incoterms</label><input type="text" name="incoterms" class="form-control form-control-sm" x-model="form.incoterms" placeholder="FOB, CIF…"></div>
            <div class="col-md-3"><label class="form-label">Port of loading</label><input type="text" name="port_of_loading" class="form-control form-control-sm" x-model="form.port_of_loading"></div>
            <div class="col-md-3"><label class="form-label">Port of discharge</label><input type="text" name="port_of_discharge" class="form-control form-control-sm" x-model="form.port_of_discharge"></div>
            <div class="col-md-3"><label class="form-label">Freight</label><input type="number" min="0" step="0.01" name="freight" class="form-control form-control-sm" x-model="form.freight" placeholder="0.00"></div>

            <div class="col-12">
              <div class="d-flex align-items-center mb-1"><label class="form-label fw-semibold mb-0">Order Lines</label>
                <button type="button" class="btn btn-outline-primary btn-sm ms-auto" @click="addItem()"><i class="bi bi-plus me-1"></i>Add Line</button></div>
              <table class="table table-sm border rounded-3">
                <thead class="table-light"><tr><th style="width:50%">Product</th><th>Quantity</th><th>Unit Price</th><th>Total</th><th></th></tr></thead>
                <tbody>
                  <template x-for="(it,i) in form.items" :key="i">
                    <tr>
                      <td><select class="form-select form-select-sm" :name="`items[${i}][product_id]`" x-model="it.product_id" required>
                        <option value="">Select product…</option>
                        <template x-for="p in products" :key="p.id"><option :value="p.id" x-text="p.name + ' (' + p.prn + ')'"></option></template>
                      </select></td>
                      <td><input type="number" min="1" class="form-control form-control-sm" :name="`items[${i}][quantity]`" x-model="it.quantity" required></td>
                      <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" :name="`items[${i}][unit_price]`" x-model="it.unit_price" placeholder="0.00"></td>
                      <td class="align-middle text-muted-sm" x-text="form.currency + ' ' + ((parseFloat(it.unit_price||0)*parseInt(it.quantity||0)).toFixed(2))"></td>
                      <td><button type="button" class="btn btn-outline-danger btn-sm btn-icon" @click="removeItem(i)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                  </template>
                </tbody>
              </table>
              <div class="d-flex justify-content-end"><div class="text-end"><div class="text-muted-sm">Grand total (incl. freight)</div><div class="fw-bold fs-5"><span x-text="form.currency"></span> <span x-text="grandTotal"></span></div></div></div>
            </div>
            <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control form-control-sm" rows="2" x-model="form.remarks" placeholder="Special instructions…"></textarea></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" @click="showAddModal=false">Cancel</button>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="submitForm('draft')"><i class="bi bi-floppy me-1"></i>Save Draft</button>
          <button type="button" class="btn btn-primary btn-sm" @click="submitForm('sent')"><i class="bi bi-send me-1"></i>Submit PO</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="modal-backdrop fade show" x-show="showAddModal" @click="showAddModal=false" x-cloak></div>
