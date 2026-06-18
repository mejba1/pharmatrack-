<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1e293b">
  <div style="max-width:560px;margin:0 auto;padding:24px">
    <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(2,6,23,.08)">

      <div style="background:linear-gradient(135deg,#10b981,#059669);padding:32px 28px;text-align:center;color:#fff">
        <div style="font-size:13px;font-weight:700;letter-spacing:2px;opacity:.85">PHARMATRACK</div>
        <div style="margin:14px auto 10px;width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.18);line-height:56px;font-size:30px">✓</div>
        <h1 style="margin:0;font-size:22px;font-weight:800">Genuine Product Confirmed</h1>
      </div>

      <div style="padding:28px">
        <p style="margin:0 0 16px;font-size:15px">Dear {{ $name }},</p>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#475569">
          Thank you for verifying your product with PharmaTrack. We're glad to confirm this item is
          <strong style="color:#059669">genuine and authentic</strong>. Here are the full details:
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:14px">
          @php
            $rows = [
              ['Product', $product?->name],
              ['Generic name', $product?->generic_name],
              ['Strength / Form', trim(($product?->strength ?? '').' '.($product?->dosage_form ? ucfirst(str_replace('_',' ',$product->dosage_form)) : ''))],
              ['Batch number', $batch?->batch_number],
              ['Batch reg. (BRN)', $batch?->brn],
              ['Manufactured', optional($batch?->manufacture_date)->format('d M Y')],
              ['Expires', optional($batch?->expiry_date)->format('d M Y')],
              ['Serial number', '#'.$unit->serial_number],
              ['Manufacturer', $product?->manufacturer_name],
              ['Country of origin', $product?->country_of_origin],
            ];
          @endphp
          @foreach($rows as $r)
            @if($r[1])
              <tr>
                <td style="padding:10px 0;color:#94a3b8;border-bottom:1px solid #f1f5f9;width:42%">{{ $r[0] }}</td>
                <td style="padding:10px 0;font-weight:600;text-align:right;border-bottom:1px solid #f1f5f9">{{ $r[1] }}</td>
              </tr>
            @endif
          @endforeach
        </table>

        <div style="margin-top:22px;padding:14px 16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;font-size:13px;color:#065f46">
          🛡️ Always buy medicines from authorised pharmacies and verify the QR code on every pack.
        </div>
      </div>

      <div style="padding:18px 28px;background:#f8fafc;border-top:1px solid #f1f5f9;text-align:center;font-size:12px;color:#94a3b8">
        Secured by PharmaTrack Anti-Counterfeit · This is an automated confirmation.
      </div>
    </div>
  </div>
</body>
</html>
