# Inventory Software — Module Workflows

This document summarizes the main business workflows represented in the project. It is intended as a high-level guide, not a detailed technical specification.

## End-to-end workflow

**Admin setup → production or supplier stock → warehouse receiving → allocation to shop/channels → sale and dispatch → returns or exchanges → reports, audit, and backup.**

## 1. Admin and master setup

The administrator prepares staff access, business locations, and product information used by the other modules.

```mermaid
flowchart LR
    A["Create staff"] --> B["Set roles and permissions"]
    B --> C["Set up production units, warehouses, shops and channels"]
    C --> D["Create product and variant masters"]
    D --> E["Add sellers, customers and warehouse locations"]
    E --> F["Modules ready for daily work"]
```

## 2. Production

Fabric and production records are tracked from roll purchase through QC and dispatch.

```mermaid
flowchart LR
    A["Record fabric purchase and rolls"] --> B["Label rolls with QR"]
    B --> C["Record roll damage, if any"]
    C --> D["Create and approve production program"]
    D --> E["Cut fabric and record usage"]
    E --> F["Stitch garments"]
    F --> G["Apply size and brand labels"]
    G --> H["Inspect in QC"]
    H --> I{"QC result"}
    I -->|"Pass"| J["Create finished-goods batch"]
    I -->|"Rework"| K["Send for rework"]
    I -->|"Reject"| L["Record rejected goods"]
    J --> M["Create outward dispatch"]
```

## 3. Purchase mapping and QR generation

After a purchase is recorded, map each purchase line to its catalog category and variant, and set its prices. Saving the mapping creates or updates the catalog variant and creates the GRN; it does not generate QR labels. Generate QR batches manually afterward from the matching Fabric Rolls, Raw Materials, or Third-Party Products area. The variant ID is shown in the generator as a reference, while the existing QR label code logic remains unchanged. Stock is posted separately from the generated batch; fabric still requires QC first.

The Return QR area is currently a disabled preview and does not generate labels.

## 4. Warehouse receiving and stock

The warehouse receives finished production, supplier goods, and returned goods, then verifies and stores them.

```mermaid
flowchart LR
    A["Production outward or supplier delivery"] --> B["Create inward / GRN"]
    B --> C["Scan and verify items or QR codes"]
    C --> D{"Quantity and condition correct?"}
    D -->|"Yes"| E["Confirm receipt"]
    D -->|"No"| F["Record discrepancy or damage"]
    E --> G["Store in warehouse location"]
    G --> H["Warehouse stock available"]
```

## 5. Warehouse allocation and dispatch

Stock is reserved and sent to a shop or sales channel after a request is processed.

```mermaid
flowchart LR
    A["Shop or channel requests stock"] --> B["Warehouse reviews request"]
    B --> C["Approve allocation"]
    C --> D["Reserve stock"]
    D --> E["Pick and scan items"]
    E --> F["Pack cartons"]
    F --> G["Dispatch with outward / gate pass"]
    G --> H["Destination receives and verifies"]
    H --> I["Destination stock updated"]
```

## 6. Retail shop and POS

The shop receives stock, sells it at POS, and records the sale and payment.

```mermaid
flowchart LR
    A["Receive and verify shop inward"] --> B["Shop inventory available"]
    B --> C["Scan product at POS"]
    C --> D["Add customer and apply sale details"]
    D --> E["Record payment"]
    E --> F["Complete sale"]
    F --> G["Reduce shop stock"]
    F --> H["Print invoice"]
```

## 7. WhatsApp sales channel

Staff record WhatsApp orders and process them using the channel's available stock.

```mermaid
flowchart LR
    A["Create WhatsApp order"] --> B["Check channel stock"]
    B --> C{"Stock available?"}
    C -->|"No"| D["Request stock from warehouse"]
    D --> E["Receive and verify channel inward"]
    E --> B
    C -->|"Yes"| F["Process order"]
    F --> G["Dispatch to customer"]
    G --> H["Update order history"]
```

## 8. Website sales channel

Website orders follow a similar stock and fulfillment workflow, using website-channel inventory.

```mermaid
flowchart LR
    A["Create website order"] --> B["Check website-channel stock"]
    B --> C{"Stock available?"}
    C -->|"No"| D["Request stock from warehouse"]
    D --> E["Receive and verify channel inward"]
    E --> B
    C -->|"Yes"| F["Process order"]
    F --> G["Dispatch to customer"]
    G --> H["Update order history"]
```

## 9. Returns and exchanges

Returns are looked up against the original sale or order. The returned item is inspected before stock and refund actions are recorded.

```mermaid
flowchart LR
    A["Find original sale or order"] --> B["Select returned item"]
    B --> C["Inspect item condition"]
    C --> D{"Condition"}
    D -->|"Good"| E["Return to available stock"]
    D -->|"Damaged"| F["Move to damaged stock / resolution"]
    E --> G{"Refund or exchange?"}
    F --> G
    G -->|"Refund"| H["Record refund or credit"]
    G -->|"Exchange"| I["Check replacement stock and price difference"]
    I --> J["Issue replacement and update stock"]
    H --> K["Create return document"]
    J --> K
```

## 10. Cash and expenses

The shop cash module tracks drawer shifts and transactions. Expenses are also recorded for operational areas and channels.

```mermaid
flowchart LR
    A["Open cash register / shift"] --> B["Record sales cash"]
    B --> C["Record cash-in, expenses or bank deposit"]
    C --> D["Review cash ledger"]
    D --> E["Count cash and close shift"]
    E --> F["Compare expected and counted cash"]
    G["Record operational or channel expense"] --> H["Save expense and voucher"]
```

## 11. Assets and maintenance

Equipment is registered and tracked through maintenance and transfers.

```mermaid
flowchart LR
    A["Register asset"] --> B["Label or scan asset"]
    B --> C["Record maintenance when needed"]
    C --> D["Transfer asset between locations if needed"]
    D --> E["Review asset records and reports"]
```

## 12. Reports, settings, audit, and backup

Managers review operational results; administrators manage system configuration and data protection.

```mermaid
flowchart LR
    A["Production, stock, sales and expense activity"] --> B["Reports and dashboards"]
    B --> C["Review inventory, production, sales and profitability"]
    D["Admin updates company, inventory, security or backup settings"] --> E["Settings saved"]
    F["Sensitive system activity"] --> G["Audit logs"]
    H["Database backup"] --> I["Store or export backup"]
    I --> J["Restore if required"]
```

## Invoice example note

The supplied sample invoice shows ₹10,500 as the taxable amount and ₹525 as tax, while the displayed grand total remains ₹10,500. If tax is added on top, the total would be ₹11,025. Confirm whether the sample is intended to show tax-inclusive pricing or needs a corrected total.
