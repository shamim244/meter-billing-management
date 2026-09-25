# 06 — NBPDCL Billing & Extraction Engine

The **NBPDCL Billing & Extraction Engine** handles electricity bill parsing, text/unicode extraction from official utility bill PDFs, meter reading calculations, and cascade-protected historical ledger maintenance.

---

## 📑 Jasper Unicode PDF Extraction & Auto-Fallback

NBPDCL electricity bills generated via JasperReports contain mixed character encodings, table layouts, and embedded Hindi/Unicode font subsets.

```
Incoming Bill PDF
       │
       ▼
Jasper Unicode Extractor (Primary Engine)
       │
       ├─ SUCCESS: Returns Consumer No, Name, Connected Load, Previous Reading, Net Payable
       │
       └─ FAILURE / SCANNED PDF:
               │
               ▼
       Smart Auto-Fallback Engine
               ├─ OCR Text Extractor (Tesseract / Cloud Vision fallback)
               └─ Auto-detect digital stamp & official QR code signature
```

### Extraction Parameters:
* **Consumer Account (CA Number)**: 9 to 11 digit unique identifier.
* **MRU / Book Number**: Meter Reading Unit code (e.g. `M-101`, `K-202`).
* **Connected Load**: Load in kW or HP.
* **Tariff Category**: `DS-II` (Domestic Rural), `NDS-I` (Non-Domestic Commercial), `LTIS` (Industrial).
* **Previous Meter Reading**: Official baseline reading from the utility provider.
* **Total Due & Net Amount with Rebate**.

---

## ⚡ Working Reading Logic & Calculations

In rural billing operations, bill collectors must estimate or verify meter readings based on historical consumption when meters are inaccessible or defective:

$$\text{Estimated Consumption} = \frac{\text{Sum of Last 3 Months Units}}{3} \times (1 \pm \text{Tuning Factor})$$

$$\text{Current Working Reading} = \max(\text{Official Previous Reading}, \text{Calculated Working Reading})$$

### Invariant Rules:
1. **Never Decrement**: The working reading can **never** be less than the official previous reading recorded on the utility PDF.
2. **Reading Source Flag**: Every reading record maintains `reading_source` (`official`, `manual`, or `projected`) to preserve audit accountability.

---

## 🛡️ Cascade Protection Mechanism

Once an agent or worker submits a reading or payment confirmation for a billing cycle:
* The bill status is updated to `Submitted` or `Approved`.
* **Cascade Protection activates**: Bulk projection operations and automated batch imports are strictly prohibited from overwriting submitted bills.
* Overriding a submitted bill requires an administrative `force` flag and is logged in the permanent audit trail.
