@extends('layouts.admin')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --rose-primary: #EC3D8F;
            --rose-primary-hover: #D8287C;
            --rose-primary-light: #FDE8F1;
            --rose-border: #F1DFE7;
            --rose-border-light: #F8EDF2;
            --rose-text: #1F2937;
            --rose-muted: #6B7280;
            --rose-card-bg: #FFFFFF;
            --rose-radius: 10px;
            --rose-shadow: 0 1px 4px rgba(0, 0, 0, 0.04), 0 2px 8px rgba(236, 61, 143, 0.03);
        }

        .bouquet-page {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--rose-text);
            padding: 8px 0 32px;
        }

        /* ── Common Card Structure ────────────────────────── */
        .fee-section,
        .template-form-header,
        .template-basic-card,
        .template-validation-card,
        .bom-group,
        .template-list {
            background: var(--rose-card-bg);
            border: 1px solid var(--rose-border);
            border-radius: var(--rose-radius);
            box-shadow: var(--rose-shadow);
        }

        /* ── 2. CẤU HÌNH PHỤ PHÍ ────────────────────────────── */
        .fee-section {
            padding: 16px 20px;
            margin-bottom: 22px;
            transition: all 0.2s ease;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            user-select: none;
        }
        .section-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #831843;
            margin: 0 0 3px;
        }
        .section-header p {
            font-size: 0.84rem;
            color: var(--rose-muted);
            margin: 0;
        }
        .btn-toggle-fee {
            background: #FFF9FB;
            border: 1px solid var(--rose-border);
            color: var(--rose-primary);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .btn-toggle-fee:hover {
            background: var(--rose-primary-light);
            border-color: var(--rose-primary);
            color: var(--rose-primary-hover);
        }
        .btn-toggle-fee i {
            font-size: 0.78rem;
            transition: transform 0.25s ease;
        }
        .fee-section.collapsed .btn-toggle-fee i {
            transform: rotate(180deg);
        }
        .fee-section.collapsed .fee-section-body {
            display: none;
        }
        .fee-section-body {
            margin-top: 16px;
            transition: all 0.3s ease;
        }

        .fee-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: flex-end;
            background: #FFF9FB;
            border: 1px solid var(--rose-border-light);
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .fee-form .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 4px;
        }
        .fee-form .form-control,
        .fee-form .form-select {
            font-size: 0.88rem;
            border-radius: 6px;
            border: 1px solid #D1D5DB;
            padding: 7px 11px;
        }
        .fee-form .btn-save-fee {
            background: var(--rose-primary);
            color: #FFFFFF;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 7px 18px;
            border-radius: 6px;
            white-space: nowrap;
            transition: all 0.15s ease;
            height: 38px;
        }
        .fee-form .btn-save-fee:hover {
            background: var(--rose-primary-hover);
        }

        .fee-tables {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .fee-table-card {
            border: 1px solid var(--rose-border-light);
            border-radius: 8px;
            overflow: hidden;
            background: #FFFFFF;
        }
        .fee-table-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #831843;
            padding: 10px 14px;
            background: #FFF0F6;
            border-bottom: 1px solid var(--rose-border-light);
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fee-table-scroll {
            max-height: 260px;
            overflow-y: auto;
        }
        .table-fee {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .table-fee thead {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #FAF5F8;
        }
        .table-fee th {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            padding: 9px 14px;
            border-bottom: 1px solid var(--rose-border);
        }
        .table-fee td {
            padding: 9px 14px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
        }
        .table-fee tr:last-child td {
            border-bottom: none;
        }
        .table-fee tr:hover td {
            background-color: #FFF9FC;
        }

        /* ── 4. TABS (TEXT + UNDERLINE) ──────────────────────── */
        .bouquet-tabs {
            display: flex;
            gap: 28px;
            border-bottom: 1px solid var(--rose-border);
            margin-bottom: 22px;
            padding-left: 4px;
        }
        .tab-btn {
            background: none;
            border: none;
            padding: 10px 4px;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--rose-muted);
            cursor: pointer;
            position: relative;
            transition: color 0.15s ease;
        }
        .tab-btn:hover {
            color: var(--rose-primary);
        }
        .tab-btn.active {
            color: var(--rose-primary);
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2.5px;
            background: var(--rose-primary);
            border-radius: 2px 2px 0 0;
        }

        /* ── 2. HEADER FORM TẠO MẪU (STICKY ACTIONS) ────────── */
        .template-create {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .template-form-header {
            position: sticky;
            top: 10px;
            z-index: 100;
            background: #FFFFFF;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(8px);
        }
        .template-form-header h2 {
            font-size: 1.12rem;
            font-weight: 700;
            color: #831843;
            margin: 0 0 2px;
        }
        .template-form-header p {
            font-size: 0.82rem;
            color: var(--rose-muted);
            margin: 0;
        }
        .template-form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* ── 3. MAIN TOP GRID (THÔNG TIN MẪU ~70% | ĐỊNH MỨC ~30%) */
        .template-top-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(290px, 1fr);
            gap: 16px;
            align-items: stretch;
        }

        /* ── 4 & 5. THÔNG TIN MẪU COMPACT ──────────────────── */
        .template-basic-card {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 14px;
        }
        .template-basic-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #831843;
            margin: 0;
        }
        .basic-info-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .basic-row-1 {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 12px;
            align-items: flex-start;
        }
        .basic-row-2 {
            display: grid;
            grid-template-columns: 2fr 2fr;
            gap: 12px;
            align-items: flex-start;
        }
        .form-group-custom label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            line-height: 1.3;
            min-height: 18px;
        }
        .form-group-custom .form-control,
        .form-group-custom .form-select {
            font-size: 0.86rem;
            height: 38px;
            border-radius: 6px;
            border: 1px solid #D1D5DB;
            padding: 6px 10px;
        }
        .form-group-custom .form-control:focus,
        .form-group-custom .form-select:focus {
            border-color: var(--rose-primary);
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.12);
        }
        .upload-compact {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .upload-preview-box {
            width: 76px;
            height: 76px;
            min-width: 76px;
            border-radius: 6px;
            border: 1px dashed var(--rose-border);
            background: #FFF9FB;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .upload-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .upload-placeholder-text {
            font-size: 0.7rem;
            color: #9CA3AF;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            text-align: center;
            line-height: 1.1;
        }
        .upload-placeholder-text i {
            font-size: 1.15rem;
        }

        /* ── 7 & 8. KIỂM TRA ĐỊNH MỨC (CARD DỌC COMPACT) ──────── */
        .template-validation-card {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .validation-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .validation-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #831843;
            margin: 0;
        }
        .validation-subtitle {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--rose-primary);
        }
        .validation-list {
            display: flex;
            flex-direction: column;
        }
        .validation-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 0;
            border-bottom: 1px solid #F3F4F6;
            min-height: 40px;
        }
        .validation-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .val-label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #374151;
            min-width: 68px;
        }
        .val-ratio {
            font-size: 0.86rem;
            font-weight: 700;
            color: #1F2937;
            margin: 0 auto 0 10px;
            font-variant-numeric: tabular-nums;
        }
        .summary-badge {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            white-space: nowrap;
        }
        .summary-badge.status-ok {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .summary-badge.status-warn {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }
        .summary-badge.status-danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .summary-badge.status-gray {
            background: #F3F4F6;
            color: #4B5563;
            border: 1px solid #E5E7EB;
        }

        /* ── 7 & 8. THÀNH PHẦN BOM (CỐ ĐỊNH 6 NHÓM, GRID 2 CỘT) ─ */
        .bom-section {
            width: 100%;
        }
        .bom-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .bom-group {
            padding: 16px;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .bom-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        .bom-group-header h3 {
            font-size: 0.94rem;
            font-weight: 700;
            color: #831843;
            margin: 0;
        }
        .bom-group-header span {
            font-size: 0.76rem;
            color: var(--rose-muted);
            display: block;
        }

        /* ── 9. SEARCHABLE MULTI-SELECT PICKER ────────────────── */
        .bom-picker {
            position: relative;
            margin-bottom: 8px;
        }
        .picker-trigger {
            background: #FFFFFF;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 0.86rem;
            color: #4B5563;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .picker-trigger:hover,
        .picker-trigger:focus {
            border-color: var(--rose-primary);
            box-shadow: 0 0 0 2px rgba(236, 61, 143, 0.1);
        }
        .picker-placeholder {
            color: #9CA3AF;
        }
        .picker-selected-text {
            color: #111827;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: calc(100% - 24px);
        }
        .bom-picker.open {
            z-index: 1060;
        }
        .picker-arrow {
            font-size: 0.75rem;
            color: #9CA3AF;
            transition: transform 0.15s ease;
        }
        .bom-picker.open .picker-arrow {
            transform: rotate(180deg);
        }

        /* Dropdown search */
        .picker-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            z-index: 1050;
            padding: 8px;
            display: none;
        }
        .bom-picker.open .picker-dropdown {
            display: block;
        }
        .picker-search-wrap {
            margin-bottom: 6px;
        }
        .picker-search-input {
            width: 100%;
            border: 1px solid #D1D5DB;
            border-radius: 5px;
            padding: 6px 9px;
            font-size: 0.84rem;
        }
        .picker-search-input:focus {
            outline: none;
            border-color: var(--rose-primary);
        }
        .picker-list {
            max-height: 220px;
            overflow-y: auto;
        }
        .picker-item {
            padding: 7px 10px;
            font-size: 0.84rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.1s ease;
        }
        .picker-item-left {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }
        .picker-item-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stock-badge {
            font-size: 0.72rem;
            padding: 2px 7px;
            border-radius: 9999px;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-available {
            background: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .badge-out-of-stock {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        .picker-item:hover,
        .picker-item.highlighted {
            background: #FDF2F8;
            color: var(--rose-primary);
        }
        .picker-item.selected-disabled {
            opacity: 0.45;
            cursor: not-allowed;
            background: #F3F4F6;
        }
        .picker-item.out-of-stock,
        .picker-item.disabled {
            opacity: 0.55;
            cursor: not-allowed;
            background: #F9FAFB !important;
            pointer-events: none;
        }

        /* ── Autocomplete gợi ý tên mẫu đã có ── */
        .tpl-name-input-wrapper {
            position: relative;
        }
        .tpl-name-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 1060;
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .tpl-name-dropdown-header {
            padding: 6px 10px;
            background: #F9FAFB;
            border-bottom: 1px solid #E5E7EB;
            font-size: 0.75rem;
            color: #6B7280;
        }
        .tpl-name-list {
            max-height: 220px;
            overflow-y: auto;
        }
        .tpl-name-item {
            padding: 7px 10px;
            font-size: 0.84rem;
            color: #374151;
            border-bottom: 1px solid #F3F4F6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        .tpl-name-item:hover {
            background: #F9FAFB;
        }
        .tpl-name-item.match-highlight {
            background: #FEF2F2;
            color: #DC2626;
            font-weight: 600;
        }
        .tpl-name-badge-exists {
            font-size: 0.7rem;
            padding: 1px 6px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 4px;
            font-weight: 600;
        }
        .input-invalid-feedback {
            font-size: 0.82rem;
            color: #DC2626;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: 500;
        }
        .input-invalid-border {
            border-color: #EF4444 !important;
            box-shadow: 0 0 0 1px #EF4444 !important;
        }

        /* ── 13 & 14. COMPACT SELECTED ITEMS (CHỐNG PHÌNH TRANG) */
        .bom-selected {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 10px;
            min-height: 40px;
            max-height: 150px;
            overflow-y: auto;
            padding: 4px 2px;
        }
        .selected-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FFFDFE;
            border: 1px solid #F1DFE7;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.84rem;
            min-width: 0;
            transition: border-color 0.15s ease;
        }
        .selected-chip:hover {
            border-color: #FBCFE8;
            background: #FFFFFF;
        }
        .chip-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
            margin-right: 6px;
        }
        .chip-name {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chip-stock-info {
            font-size: 0.72rem;
            color: #6B7280;
            font-weight: 500;
        }
        .chip-qty-wrap {
            display: flex;
            align-items: center;
            gap: 3px;
            flex-shrink: 0;
        }
        .chip-qty-input {
            width: 54px;
            height: 24px;
            padding: 0 4px;
            text-align: center;
            font-size: 0.82rem;
            font-weight: 600;
            border: 1px solid #D1D5DB;
            border-radius: 4px;
        }
        .chip-unit {
            font-size: 0.74rem;
            color: var(--rose-muted);
        }
        .btn-chip-remove {
            background: none;
            border: none;
            color: #9CA3AF;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0 0 0 6px;
            cursor: pointer;
        }
        .btn-chip-remove:hover {
            color: #EF4444;
        }
        .empty-state-chip {
            grid-column: 1 / -1;
            color: #9CA3AF;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            height: 34px;
            font-style: italic;
        }

        /* ── Action Buttons ────────────────────────────────── */
        .btn-action-primary {
            background: var(--rose-primary);
            color: #FFFFFF;
            border: none;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }
        .btn-action-primary:hover {
            background: var(--rose-primary-hover);
        }
        .btn-action-secondary {
            background: #FFFFFF;
            color: #4B5563;
            border: 1px solid #D1D5DB;
            font-weight: 600;
            font-size: 0.88rem;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
        }
        .btn-action-secondary:hover {
            background: #F9FAFB;
            border-color: #9CA3AF;
        }

        /* ── 24. TAB DANH SÁCH MẪU ──────────────────────────── */
        .template-list {
            padding: 20px;
        }
        .list-toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            max-width: 540px;
        }
        .table-wrapper {
            overflow-x: auto;
        }
        .table-template {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .table-template th {
            background: #FAF5F8;
            font-size: 0.78rem;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            padding: 10px 14px;
            border-bottom: 1px solid var(--rose-border);
        }
        .table-template td {
            padding: 12px 14px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
        }
        .table-template tr:hover td {
            background: #FFFDFE;
        }
        .template-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #E5E7EB;
        }

        /* ── TAB BÓ HOA SẴN (PREMADE PAGE) ─────────────────── */
        .premade-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .premade-header {
            background: var(--rose-card-bg);
            border: 1px solid var(--rose-border);
            border-radius: var(--rose-radius);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: var(--rose-shadow);
        }
        .premade-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #831843;
            margin: 0 0 3px;
        }
        .premade-header p {
            font-size: 0.84rem;
            color: var(--rose-muted);
            margin: 0;
        }
        .premade-create-panel {
            background: var(--rose-card-bg);
            border: 1px solid var(--rose-border);
            border-radius: var(--rose-radius);
            padding: 20px;
            box-shadow: 0 4px 14px rgba(236, 61, 143, 0.08);
            animation: fadeInDown 0.2s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .premade-create-panel h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #831843;
            margin: 0;
        }
        .create-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1.3fr) auto;
            gap: 14px;
            align-items: flex-end;
            background: #FFFDFE;
            border: 1px solid var(--rose-border-light);
            border-radius: 8px;
            padding: 14px 16px;
        }
        .create-grid .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .create-grid .form-control,
        .create-grid .form-select {
            font-size: 0.88rem;
            height: 38px;
            border-radius: 6px;
            border: 1px solid #D1D5DB;
            padding: 6px 10px;
        }
        .create-grid .form-control:focus,
        .create-grid .form-select:focus {
            border-color: var(--rose-primary);
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.12);
        }
        .material-preview {
            background: #FAFAFA;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .preview-table-wrap {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            background: #FFFFFF;
        }
        .table-preview-materials {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }
        .table-preview-materials th {
            position: sticky;
            top: 0;
            background: #F9FAFB;
            font-size: 0.76rem;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            padding: 8px 12px;
            border-bottom: 1px solid #E5E7EB;
            z-index: 2;
        }
        .table-preview-materials td {
            padding: 8px 12px;
            border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
        }
        .table-preview-materials tr.row-missing {
            background-color: #FEF2F2 !important;
        }
        .table-preview-materials tr.row-missing td {
            color: #991B1B;
            font-weight: 600;
        }
        .premade-list-section {
            background: var(--rose-card-bg);
            border: 1px solid var(--rose-border);
            border-radius: var(--rose-radius);
            padding: 20px;
            box-shadow: var(--rose-shadow);
        }

        /* ── 25. RESPONSIVE ─────────────────────────────────── */
        @media (max-width: 1200px) {
            .fee-form {
                grid-template-columns: 1fr 1fr;
            }
            .template-top-grid {
                grid-template-columns: 1.5fr 1fr;
            }
        }
        @media (max-width: 992px) {
            .fee-tables {
                grid-template-columns: 1fr;
            }
            .template-top-grid {
                grid-template-columns: 1fr;
            }
            .bom-grid {
                grid-template-columns: 1fr;
            }
            .create-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 768px) {
            .fee-form {
                grid-template-columns: 1fr;
            }
            .template-form-header,
            .premade-header {
                position: static;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .template-form-actions {
                width: 100%;
                justify-content: flex-end;
            }
            .create-grid {
                grid-template-columns: 1fr;
            }
            .basic-row-1, .basic-row-2 {
                grid-template-columns: 1fr;
            }
            .bom-selected {
                grid-template-columns: 1fr;
            }
            .bouquet-tabs {
                gap: 14px;
                overflow-x: auto;
            }
        }
        /* ── HEADER TRANG ĐỒNG BỘ ROSESHOP ── */
        .rs-header-block {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }
        .rs-header-title {
            font-size: 20px;
            font-weight: 700;
            color: #101828;
            margin: 0 0 4px 0;
        }
        .rs-header-subtitle {
            font-size: 14px;
            color: #667085;
            margin: 0;
        }

        /* ── HỆ THỐNG GIAO DIỆN BẢNG & PHÂN TRANG CHUẨN ROSESHOP (ĐỒNG BỘ VỚI SẢN PHẨM) ── */
        .rs-main-card {
            background: #ffffff;
            border: 1px solid #EAECF0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .rs-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #EAECF0;
            flex-wrap: wrap;
        }
        .rs-filter-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rs-filter-label {
            font-size: 13.5px;
            font-weight: 600;
            color: #344054;
            margin: 0;
            white-space: nowrap;
        }
        .rs-filter-select {
            height: 40px;
            padding: 0 32px 0 12px;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 13.5px;
            color: #101828;
            background: #ffffff;
            outline: none;
            transition: all 0.15s ease;
        }
        .rs-filter-select:focus,
        .rs-filter-search-box input:focus {
            border-color: #EC3D8F !important;
            box-shadow: 0 0 0 3px rgba(236, 61, 143, 0.15) !important;
        }
        .rs-filter-search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
            display: flex;
            align-items: center;
        }
        .rs-filter-search-box svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #667085;
            pointer-events: none;
            z-index: 2;
            width: 16px;
            height: 16px;
        }
        .rs-filter-search-box input {
            width: 100%;
            height: 40px;
            padding-left: 40px !important;
            padding-right: 14px;
            border-radius: 8px;
            border: 1px solid #D0D5DD !important;
            font-size: 14px;
            color: #101828;
            background-color: #ffffff;
            outline: none;
            box-shadow: none !important;
            transition: all 0.15s ease;
        }
        .rs-btn-filter-submit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            padding: 0 18px;
            background-color: #EC3D8F;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .rs-btn-filter-submit:hover {
            background-color: #D82677;
        }
        .rs-btn-filter-reset {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            height: 40px;
            padding: 0 14px;
            background-color: #F2F4F7;
            color: #344054;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 13.5px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .rs-btn-filter-reset:hover {
            background-color: #E4E7EC;
            color: #101828;
        }
        .rs-card-title-bar {
            padding: 16px 20px 14px 20px;
            background: #ffffff;
            border-bottom: 1px solid #EAECF0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .rs-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: #101828;
        }
        .rs-card-title svg {
            color: #475467;
        }
        .rs-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: #F2F4F7;
            color: #344054;
            font-size: 12.5px;
            font-weight: 600;
            margin-left: 4px;
        }
        .rs-table-container {
            width: 100%;
            overflow-x: auto;
            position: relative;
        }
        .rs-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            table-layout: fixed;
            min-width: 1120px;
        }
        .rs-table thead tr {
            background-color: #F8F9FA !important;
        }
        .rs-table th {
            background-color: #F8F9FA !important;
            padding: 12px 12px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            color: #475467 !important;
            text-transform: none;
            border-bottom: 1px solid #EAECF0 !important;
            white-space: nowrap !important;
        }
        .rs-table tbody tr {
            border-bottom: 1px solid #EAECF0;
            transition: background-color 0.15s ease;
        }
        .rs-table tbody tr:hover {
            background-color: #F9FAFB;
        }
        .rs-table td {
            padding: 12px 12px !important;
            font-size: 13.5px;
            color: #344054;
            vertical-align: middle;
            border-bottom: 1px solid #EAECF0;
        }
        .rs-product-thumb {
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            max-width: 44px;
            max-height: 44px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .rs-product-thumb-img {
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            display: block;
        }
        .rs-product-thumb-placeholder {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 20px;
        }
        .rs-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .rs-action-btn {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #D0D5DD;
            background: #ffffff;
            color: #667085;
            cursor: pointer;
            transition: all 0.15s ease;
            padding: 0;
            text-decoration: none;
        }
        .rs-action-btn svg {
            width: 15px !important;
            height: 15px !important;
            stroke-width: 1.8px;
            stroke: currentColor;
            fill: none;
            display: block;
            flex-shrink: 0;
        }
        .rs-action-btn:hover {
            background-color: #F2F4F7 !important;
            color: #101828 !important;
            border-color: #98A2B3 !important;
        }
        .rs-action-btn.danger:hover {
            background-color: #FEF3F2 !important;
            color: #D92D20 !important;
            border-color: #FECDCA !important;
        }
        .rs-table-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #ffffff;
            border-top: 1px solid #EAECF0;
            flex-wrap: wrap;
            gap: 12px;
        }
        .rs-footer-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .rs-per-page-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: #475467;
        }
        .rs-per-page-select {
            height: 36px;
            padding: 0 10px;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            font-size: 13.5px;
            color: #101828;
            background: #ffffff;
            outline: none;
            cursor: pointer;
        }
        .rs-records-count {
            font-size: 13.5px;
            color: #667085;
            white-space: nowrap;
        }
        .rs-table-footer .rs-pagination,
        .rs-table-footer .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .rs-table-footer .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 8px !important;
            border: 1px solid #D0D5DD;
            background: #ffffff;
            color: #344054;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .rs-table-footer .page-item.active .page-link,
        .rs-table-footer .page-item.active > .page-link,
        .rs-pagination .page-item.active .page-link,
        .rs-pagination .page-item.active > .page-link,
        .pagination .page-item.active .page-link,
        .pagination .page-item.active > .page-link,
        ul.pagination li.active span.page-link,
        ul.pagination li.active a.page-link,
        .page-item.active .page-link,
        .active > .page-link {
            background-color: #EC3D8F !important;
            background: #EC3D8F !important;
            border-color: #EC3D8F !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;
        }
        .rs-table-footer .page-item.disabled .page-link {
            color: #D0D5DD;
            background-color: #F9FAFB;
            border-color: #EAECF0;
            cursor: not-allowed;
        }
        .rs-table-footer .page-item .page-link:hover:not(.active):not(.disabled) {
            background-color: #F2F4F7;
            border-color: #D0D5DD;
            color: #101828;
        }
    </style>
@endpush

@section('content')

<div class="bouquet-page">
    <div class="container-fluid px-3 px-lg-4">

        {{-- 1. HEADER TRANG: Tiêu đề Quản lý mẫu bó hoa (Đồng bộ các trang quản lý khác) --}}
        <div class="rs-header-block">
            <div>
                <h1 class="rs-header-title">Quản lý mẫu bó hoa</h1>
                <p class="rs-header-subtitle">Theo dõi danh sách mẫu bó hoa, cấu hình phụ phí và quản lý bó hoa sẵn</p>
            </div>
        </div>

        {{-- Flash message --}}
        <div id="bo-message" class="alert d-none py-2 px-3 mb-3 font-semibold rounded-2" role="status"></div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 mb-3 border-0 rounded-2" style="background:#ECFDF5; color:#065F46;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 border-0 rounded-2" style="background:#FEF2F2; color:#991B1B;">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $boController = \App\Http\Controllers\Admin\BoHoaController::class;
            $sizesBo  = $boController::SO_BONG;
            $kieusBo  = $boController::KIEU_DANG;
            $concepts = $boController::CONCEPT;

            $giayGoiQty = $boController::GIAY_GOI_QTY;
            $ruyBangQty = $boController::RUY_BANG_QTY;
            $xopQty     = $boController::XOP_QTY;
            $phuKienMax = $boController::PHU_KIEN_MAX;

            $phuPhiMap = $phuPhiBoHoa->keyBy(fn ($p) => $p->loai . '|' . $p->gia_tri);

            // Tồn kho khả dụng thực tế của nguyên liệu (đã trừ đang giữ - RESERVED)
            $tonKhoKhaDungMap = $tonKhoKhaDungMap ?? \App\Models\ChiTietNhap::whereHas('phieuNhap', fn($q) => $q->where('trang_thai', 'CONFIRMED'))
                ->where('so_luong_con_lai', '>', 0)
                ->selectRaw('ma_san_pham, SUM(so_luong_con_lai) as kha_dung')
                ->groupBy('ma_san_pham')
                ->pluck('kha_dung', 'ma_san_pham');

            $nguyenLieuMap = $nguyenLieuBoHoa->keyBy('ma_san_pham');
            $mauMap        = $mauBoHoas->keyBy('ma_mau_bo_hoa');

            $mauBoHoaData = $mauBoHoas->map(fn($m) => [
                'id'         => $m->ma_mau_bo_hoa,
                'ten'        => $m->ten_mau,
                'size'       => $m->size,
                'kieu'       => $kieusBo[$m->kieu_bo_hoa] ?? $m->kieu_bo_hoa,
                'trang_thai' => $m->trang_thai,
                'thanh_phan' => $m->chiTietMauBoHoa->map(fn($ct) => [
                    'ma_san_pham'  => $ct->ma_san_pham,
                    'ten_san_pham' => $ct->sanPham?->ten_san_pham ?? ($nguyenLieuMap[$ct->ma_san_pham]->ten_san_pham ?? 'Sản phẩm #' . $ct->ma_san_pham),
                    'don_vi_tinh'  => $ct->sanPham?->don_vi_tinh_text ?? ($nguyenLieuMap[$ct->ma_san_pham]->don_vi_tinh ?? 'cái'),
                    'vai_tro'      => $ct->vai_tro,
                    'so_luong'     => (int) $ct->so_luong,
                ])->values(),
            ])->keyBy('id');

            // Phân loại nguyên liệu chuẩn theo nhóm
            $flowerProducts     = $nguyenLieuBoHoa->filter(fn($p) => in_array($p->loai_san_pham, ['HOA_TUOI', 'HOA_GIA', 'HOA_SAP', 'HOA_GIAY_NHUN']))->values();
            $giayGoiProducts    = $nguyenLieuBoHoa->filter(fn($p) => $p->loai_san_pham === 'GIAY_GOI')->values();
            $ruyBangProducts    = $nguyenLieuBoHoa->filter(fn($p) => $p->loai_san_pham === 'RUY_BANG')->values();
            $phuKienProducts    = $nguyenLieuBoHoa->filter(fn($p) => $p->loai_san_pham === 'PHU_KIEN_TRANG_TRI')->values();
            $xopProducts        = $nguyenLieuBoHoa->filter(fn($p) => $p->loai_san_pham === 'MIENG_XOP')->values();
        @endphp

        {{-- =========================================================================
             2. CẤU HÌNH PHỤ PHÍ (CÓ MŨI TÊN ẨN / HIỆN)
        ========================================================================= --}}
        <div class="fee-section" id="fee-section">
            <div class="section-header" id="fee-section-header" title="Bấm để ẩn / hiện cấu hình phụ phí">
                <div>
                    <h2>Cấu hình phụ phí</h2>
                    <p>Thiết lập phụ phí theo Size và Kiểu dáng</p>
                </div>
                <button type="button" class="btn-toggle-fee" id="btn-toggle-fee" aria-label="Ẩn / hiện cấu hình phụ phí">
                    <span id="fee-toggle-text">Thu gọn</span>
                    <i class="bi bi-chevron-up" id="fee-toggle-icon"></i>
                </button>
            </div>

            <div class="fee-section-body" id="fee-section-body">
                {{-- Form thêm/sửa phụ phí (Desktop 4 cột) --}}
                <form id="form-fee-quick" class="fee-form" action="{{ route('admin.bo-hoa.phu-phi.store') }}" method="POST">
                    @csrf
                    <div class="field">
                        <label>Loại</label>
                        <select name="loai" id="fee-select-loai" class="form-select">
                            <option value="SIZE">Phụ phí theo Size</option>
                            <option value="KIEU_DANG">Phụ phí theo Kiểu dáng</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Giá trị</label>
                        <select name="gia_tri" id="fee-select-giatri" class="form-select" required></select>
                    </div>
                    <div class="field">
                        <label>Phụ phí (VNĐ)</label>
                        <input type="number" name="phu_phi" id="fee-input-sotien" class="form-control" min="0" step="1000" placeholder="VD: 5000" required>
                    </div>
                    <div>
                        <button type="submit" class="btn-save-fee">
                            Lưu cấu hình
                        </button>
                    </div>
                </form>

                {{-- 3. Danh sách cấu hình (2 bảng 50/50, scroll max-height 260px, header sticky) --}}
                <div class="fee-tables">
                    {{-- Bảng Size --}}
                    <div class="fee-table-card">
                        <div class="fee-table-title">
                            <span>Phụ phí theo Size</span>
                            <span class="badge bg-light text-dark border">{{ count($sizesBo) }} size</span>
                        </div>
                        <div class="fee-table-scroll">
                            <table class="table-fee">
                                <thead>
                                    <tr>
                                        <th>Size</th>
                                        <th class="text-end">Phụ phí</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sizesBo as $sizeCode => $stems)
                                        @php
                                            $phiRecord = $phuPhiMap->get("SIZE|{$sizeCode}");
                                            $phiVal = $phiRecord ? (float)$phiRecord->phu_phi : 0;
                                            $labelSize = $sizeCode === 'SPECIAL' ? 'Special' : $sizeCode;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $labelSize }}</strong>
                                                <span class="text-muted ms-1 small">({{ $stems }} bông)</span>
                                            </td>
                                            <td class="text-end fw-semibold" style="color:var(--rose-primary);">
                                                {{ number_format($phiVal, 0, ',', '.') }} đ
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-trigger-fee-edit"
                                                        data-loai="SIZE" data-giatri="{{ $sizeCode }}" data-phi="{{ $phiVal }}">
                                                    Sửa
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Bảng Kiểu dáng --}}
                    <div class="fee-table-card">
                        <div class="fee-table-title">
                            <span>Phụ phí theo Kiểu dáng</span>
                            <span class="badge bg-light text-dark border">{{ count($kieusBo) }} kiểu</span>
                        </div>
                        <div class="fee-table-scroll">
                            <table class="table-fee">
                                <thead>
                                    <tr>
                                        <th>Kiểu dáng</th>
                                        <th class="text-end">Phụ phí</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kieusBo as $kCode => $kName)
                                        @php
                                            $phiRecord = $phuPhiMap->get("KIEU_DANG|{$kCode}");
                                            $phiVal = $phiRecord ? (float)$phiRecord->phu_phi : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $kName }}</strong>
                                            </td>
                                            <td class="text-end fw-semibold text-primary">
                                                {{ number_format($phiVal, 0, ',', '.') }} đ
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none btn-trigger-fee-edit"
                                                        data-loai="KIEU_DANG" data-giatri="{{ $kCode }}" data-phi="{{ $phiVal }}">
                                                    Sửa
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================================
             4. TAB (TEXT + UNDERLINE)
        ========================================================================= --}}
        <div class="bouquet-tabs">
            <button class="tab-btn active" data-target="#tab-danh-sach-mau">Danh sách mẫu</button>
            <button class="tab-btn" data-target="#tab-tao-mau">Tạo mẫu</button>
            <button class="tab-btn" data-target="#tab-bo-hoa-san">Bó hoa sẵn</button>
        </div>

        {{-- =========================================================================
             NỘI DUNG CÁC TAB
        ========================================================================= --}}
        <div class="tab-container-content">

            {{-- ─────────────────────────────────────────────────────────────────────
                 TAB 1: DANH SÁCH MẪU
            ───────────────────────────────────────────────────────────────────── --}}
            <div class="tab-pane-item" id="tab-danh-sach-mau">
                <div class="rs-main-card">
                    {{-- Bộ lọc chuẩn RoseShop --}}
                    <div class="rs-filter-bar">
                        <div class="rs-filter-item">
                            <label class="rs-filter-label">Trạng thái:</label>
                            <select id="filter-mau-status" class="rs-filter-select">
                                <option value="">-- Tất cả trạng thái --</option>
                                <option value="ACTIVE">Hiện (Đang dùng)</option>
                                <option value="INACTIVE">Ẩn</option>
                            </select>
                        </div>

                        <div class="rs-filter-search-box">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" id="search-mau-name" placeholder="Tìm tên mẫu, mã mẫu...">
                        </div>

                        <button type="button" class="rs-btn-filter-submit" id="btn-search-mau">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <span>Lọc</span>
                        </button>
                    </div>

                    {{-- Tiêu đề bảng danh sách chuẩn --}}
                    <div class="rs-card-title-bar">
                        <div class="rs-card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                            <span>Danh sách mẫu bó hoa</span>
                            <span class="rs-count-badge" id="mau-total-count">{{ $mauBoHoas->count() }}</span>
                        </div>
                    </div>

                    {{-- Table danh sách mẫu chuẩn --}}
                    <div class="rs-table-container">
                        <table class="rs-table">
                            <thead>
                                <tr>
                                    <th style="width: 65px; min-width: 65px; text-align: center;">Ảnh</th>
                                    <th style="min-width: 180px; text-align: left;">Tên mẫu</th>
                                    <th style="width: 85px; min-width: 85px; text-align: center;">Size</th>
                                    <th style="width: 120px; min-width: 120px; text-align: center;">Kiểu dáng</th>
                                    <th style="width: 120px; min-width: 120px; text-align: center;">Concept</th>
                                    <th style="width: 165px; min-width: 165px; text-align: left;">Thời hạn</th>
                                    <th style="width: 130px; min-width: 130px; text-align: right;">Giá dự kiến</th>
                                    <th style="width: 95px; min-width: 95px; text-align: center;">Trạng thái</th>
                                    <th style="width: 90px; min-width: 90px; text-align: center;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-mau-list">
                                @forelse($mauBoHoas as $m)
                                    @php
                                        $costNL = $m->chiTietMauBoHoa->sum(fn($ct) => ($ct->sanPham?->gia_ban_hien_tai ?? 0) * $ct->so_luong);
                                        $pSize = (float)($phuPhiMap->get("SIZE|{$m->size}")?->phu_phi ?? 0);
                                        $pKieu = (float)($phuPhiMap->get("KIEU_DANG|{$m->kieu_bo_hoa}")?->phu_phi ?? 0);
                                        $giaEst = $costNL + $pSize + $pKieu;
                                    @endphp
                                    <tr class="mau-row" data-name="{{ strtolower($m->ten_mau) }} #{{ $m->ma_mau_bo_hoa }}" data-status="{{ $m->trang_thai }}">
                                        <td style="text-align: center; vertical-align: middle;">
                                            <div class="rs-product-thumb">
                                                <img src="{{ $boController::anhMau($m) }}"
                                                     alt="{{ $m->ten_mau }}"
                                                     class="rs-product-thumb-img"
                                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'rs-product-thumb-placeholder\'><i class=\'bi bi-flower1\'></i></div>';">
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="d-block text-dark text-nowrap" style="font-size:13.5px;">{{ $m->ten_mau }}</strong>
                                            <small class="text-muted text-nowrap" style="font-size:12px;">#{{ $m->ma_mau_bo_hoa }} · {{ $m->chiTietMauBoHoa->count() }} thành phần</small>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge bg-light text-dark border">
                                                Size {{ $m->size === 'SPECIAL' ? 'Special' : $m->size }}
                                            </span>
                                        </td>
                                        <td style="text-align: center;">{{ $kieusBo[$m->kieu_bo_hoa] ?? $m->kieu_bo_hoa }}</td>
                                        <td style="text-align: center;">
                                            <span class="badge bg-light text-secondary border">
                                                {{ $concepts[$m->concept] ?? $m->concept }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1" style="font-size:12px;">
                                                <div class="text-nowrap" title="Ngày tạo mẫu bó hoa">
                                                    <span class="text-muted"><i class="bi bi-calendar-event me-1"></i>Tạo:</span>
                                                    <span class="fw-medium text-dark">{{ $m->created_at ? $m->created_at->format('d/m/Y') : '—' }}</span>
                                                </div>
                                                <div class="text-nowrap" title="Hạn sử dụng khuyến nghị cho hoa tươi">
                                                    <span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>HSD:</span>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle fw-semibold" style="font-size:11px;">
                                                        {{ $m->han_su_dung_ngay ?? 5 }} ngày
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: right;" class="fw-bold text-success">
                                            {{ number_format($giaEst, 0, ',', '.') }} đ
                                        </td>
                                        <td style="text-align: center;">
                                            @if($m->trang_thai === 'ACTIVE')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hiện</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Ẩn</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <div class="rs-action-group">
                                                <button type="button" 
                                                        class="rs-action-btn"
                                                        data-bo-action="{{ route('admin.bo-hoa.mau.trang-thai', $m->ma_mau_bo_hoa) }}"
                                                        data-method="PATCH"
                                                        title="{{ $m->trang_thai === 'ACTIVE' ? 'Ẩn mẫu' : 'Bật sử dụng' }}">
                                                    @if($m->trang_thai === 'ACTIVE')
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                    @else
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                                            <line x1="2" y1="2" x2="22" y2="22"/>
                                                        </svg>
                                                    @endif
                                                </button>

                                                <button type="button" 
                                                        class="rs-action-btn danger"
                                                        data-bo-action="{{ route('admin.bo-hoa.mau.destroy', $m->ma_mau_bo_hoa) }}"
                                                        data-method="DELETE"
                                                        data-confirm="Bạn có chắc chắn muốn xóa mẫu '{{ $m->ten_mau }}'?"
                                                        {{ ($m->so_bo_hoa_chua_ban ?? 0) > 0 ? 'disabled' : '' }}
                                                        title="{{ ($m->so_bo_hoa_chua_ban ?? 0) > 0 ? 'Còn bó hoa tồn kho từ mẫu này' : 'Xóa mẫu' }}">
                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 6h18"/>
                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            Chưa có mẫu bó hoa nào. Hãy chuyển sang tab <strong>Tạo mẫu</strong> để thêm mẫu mới.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Phân trang chuẩn RoseShop cho Danh sách mẫu --}}
                    <div class="rs-table-footer" id="mau-table-footer">
                        <div class="rs-footer-left">
                            <div class="rs-per-page-inline">
                                <span class="rs-per-page-label">Số hàng mỗi trang</span>
                                <select class="rs-per-page-select" id="mau-per-page-select">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="rs-records-count" id="mau-records-count">
                                1-{{ min(10, $mauBoHoas->count()) }} / {{ $mauBoHoas->count() }} bản ghi
                            </div>
                        </div>
                        <div class="rs-footer-pagination">
                            <ul class="rs-pagination" id="mau-pagination-list"></ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────────────────────────────────────────
                 TAB 2: TẠO MẪU (THÔNG TIN MẪU -> BOM PICKERS -> ĐỊNH MỨC -> ACTIONS)
            ───────────────────────────────────────────────────────────────────── --}}
            <div class="tab-pane-item d-none" id="tab-tao-mau">
                <form id="form-create-template" class="template-create" action="{{ route('admin.bo-hoa.mau.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf

                    {{-- 2. HEADER FORM TẠO MẪU (STICKY ACTIONS) --}}
                    <div class="template-form-header">
                        <div>
                            <h2>Tạo mẫu bó hoa</h2>
                            <p>Nhập thông tin mẫu và thiết lập thành phần BOM.</p>
                        </div>
                        <div class="template-form-actions">
                            <button type="button" class="btn-action-secondary" id="btn-reset-template">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Làm mới
                            </button>
                            <button type="submit" class="btn-action-primary" id="btn-submit-template">
                                <i class="bi bi-check2-circle me-1"></i>Lưu mẫu
                            </button>
                        </div>
                    </div>

                    {{-- Thông báo lỗi / thành công trực tiếp trong tab Tạo mẫu --}}
                    <div id="template-create-message" class="alert d-none py-2 px-3 mb-2 rounded-2" role="status"></div>

                    {{-- 3. MAIN TOP GRID (THÔNG TIN MẪU ~70% | ĐỊNH MỨC ~30%) --}}
                    <div class="template-top-grid">

                        {{-- THÔNG TIN MẪU BÓ HOA --}}
                        <div class="template-basic-card">
                            <h3>Thông tin mẫu bó hoa</h3>
                            <div class="basic-info-grid">
                                {{-- Dòng 1: Tên mẫu | Kiểu dáng | Size --}}
                                <div class="basic-row-1">
                                    <div class="form-group-custom position-relative" id="tpl-name-group">
                                        <label for="input-tpl-name">Tên mẫu <span class="text-danger">*</span></label>
                                        <div class="tpl-name-input-wrapper position-relative">
                                            <input type="text" name="ten_bo_hoa" id="input-tpl-name" class="form-control" maxlength="100" placeholder="Tìm hoặc nhập tên mẫu mới..." autocomplete="off" required>
                                            <div id="tpl-name-dropdown" class="tpl-name-dropdown d-none">
                                                <div class="tpl-name-dropdown-header">
                                                    <i class="bi bi-info-circle me-1"></i>Các tên mẫu đã có (chỉ để tham khảo, không cho tạo trùng):
                                                </div>
                                                <div id="tpl-name-list" class="tpl-name-list"></div>
                                            </div>
                                        </div>
                                        <div id="tpl-name-feedback" class="input-invalid-feedback d-none">
                                            <i class="bi bi-x-circle-fill me-1"></i>Tên mẫu đã tồn tại. Vui lòng chọn tên khác.
                                        </div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Kiểu dáng <span class="text-danger">*</span></label>
                                        <select name="kieu_bo_hoa" id="select-tpl-kieu" class="form-select" required>
                                            <option value="">-- Chọn kiểu dáng --</option>
                                            @foreach($kieusBo as $kCode => $kName)
                                                @php $pRecord = $phuPhiMap->get("KIEU_DANG|{$kCode}"); @endphp
                                                <option value="{{ $kCode }}" data-phi="{{ $pRecord?->phu_phi ?? 0 }}">
                                                    {{ $kName }} (+{{ number_format($pRecord?->phu_phi ?? 0, 0, ',', '.') }} đ)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Size <span class="text-danger">*</span></label>
                                        <select name="size" id="select-tpl-size" class="form-select" required>
                                            <option value="">-- Chọn size --</option>
                                            @foreach($sizesBo as $sCode => $stems)
                                                @php $pRecord = $phuPhiMap->get("SIZE|{$sCode}"); @endphp
                                                <option value="{{ $sCode }}"
                                                        data-stems="{{ $stems }}"
                                                        data-phi="{{ $pRecord?->phu_phi ?? 0 }}"
                                                        data-giay="{{ $giayGoiQty[$sCode] ?? 4 }}"
                                                        data-ruy="{{ $ruyBangQty[$sCode] ?? 1 }}"
                                                        data-xop="{{ $xopQty[$sCode] ?? 1 }}"
                                                        data-phukien="{{ $phuKienMax[$sCode] ?? 2 }}">
                                                    Size {{ $sCode === 'SPECIAL' ? 'Special' : $sCode }} ({{ $stems }} bông · +{{ number_format($pRecord?->phu_phi ?? 0, 0, ',', '.') }} đ)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Dòng 2: Concept | Ảnh mẫu --}}
                                <div class="basic-row-2">
                                    <div class="form-group-custom">
                                        <label>Concept</label>
                                        <select name="concept" id="select-tpl-concept" class="form-select">
                                            <option value="">-- Chọn phong cách concept (tùy chọn) --</option>
                                            @foreach($concepts as $cCode => $cName)
                                                <option value="{{ $cCode }}">{{ $cName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group-custom" style="max-width: 170px;">
                                        <label>HSD hoa tươi (ngày)</label>
                                        <input type="number" name="han_su_dung_ngay" id="input-tpl-hsd" class="form-control" min="1" max="60" value="5" placeholder="5">
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Ảnh mẫu</label>
                                        <div class="upload-compact">
                                            <input type="file" name="hinh_anh" id="input-tpl-image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                            <div class="upload-preview-box" id="tpl-img-box">
                                                <img id="tpl-img-preview" src="" alt="" class="d-none">
                                                <span id="tpl-img-placeholder" class="upload-placeholder-text">
                                                    <i class="bi bi-image"></i>
                                                    <span>Chưa có ảnh</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- KIỂM TRA ĐỊNH MỨC (CARD DỌC COMPACT) --}}
                        <div class="template-validation-card">
                            <div class="validation-header">
                                <h3>Kiểm tra định mức</h3>
                                <span class="validation-subtitle" id="val-size-note">Chưa chọn size</span>
                            </div>

                            <div class="validation-list">
                                {{-- 1. Hoa --}}
                                <div class="validation-row">
                                    <span class="val-label">Hoa</span>
                                    <span class="val-ratio" id="sum-val-hoa">0 / ?</span>
                                    <span class="summary-badge status-gray" id="sum-badge-hoa">Chưa chọn size</span>
                                </div>

                                {{-- 2. Giấy gói --}}
                                <div class="validation-row">
                                    <span class="val-label">Giấy gói</span>
                                    <span class="val-ratio" id="sum-val-giay">0 / ?</span>
                                    <span class="summary-badge status-gray" id="sum-badge-giay">Chưa chọn size</span>
                                </div>

                                {{-- 3. Ruy băng --}}
                                <div class="validation-row">
                                    <span class="val-label">Ruy băng</span>
                                    <span class="val-ratio" id="sum-val-ruy">0 / ?</span>
                                    <span class="summary-badge status-gray" id="sum-badge-ruy">Chưa chọn size</span>
                                </div>

                                {{-- 4. Mút xốp --}}
                                <div class="validation-row">
                                    <span class="val-label">Mút xốp</span>
                                    <span class="val-ratio" id="sum-val-xop">0 / ?</span>
                                    <span class="summary-badge status-gray" id="sum-badge-xop">Chưa chọn size</span>
                                </div>

                                {{-- 5. Phụ kiện --}}
                                <div class="validation-row">
                                    <span class="val-label">Phụ kiện</span>
                                    <span class="val-ratio" id="sum-val-phukien">0 / ?</span>
                                    <span class="summary-badge status-gray" id="sum-badge-phukien">Chưa chọn size</span>
                                </div>

                                {{-- 6. Khả dụng kho (Tách biệt định mức BOM và khả dụng kho) --}}
                                <div class="validation-row" id="val-row-stock">
                                    <span class="val-label">Khả dụng kho</span>
                                    <span class="val-ratio" id="sum-val-stock">0 món</span>
                                    <span class="summary-badge status-gray" id="sum-badge-stock">Chưa chọn</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- 7 & 8. THÀNH PHẦN BOM (CỐ ĐỊNH 6 NHÓM, GRID 2 CỘT) --}}
                    <div class="bom-section">
                        <div class="bom-grid">

                            {{-- Nhóm 1: Hoa chính --}}
                            <div class="bom-group hoa-chinh" data-role="HOA_CHINH" data-unit="bông">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Hoa chính</h3>
                                        <span id="note-hoa-chinh">Có thể chọn nhiều loại (≥ 1 bông)</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-HOA_CHINH">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn hoa chính...">Tìm hoặc chọn hoa chính...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm hoa chính...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-HOA_CHINH"></div>
                            </div>

                            {{-- Nhóm 2: Hoa trang trí --}}
                            <div class="bom-group hoa-trang-tri" data-role="HOA_TRANG_TRI" data-unit="bông">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Hoa trang trí</h3>
                                        <span id="note-hoa-trang-tri">Hoa phụ / hoa đệm theo size</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-HOA_TRANG_TRI">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn hoa trang trí...">Tìm hoặc chọn hoa trang trí...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm hoa trang trí...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-HOA_TRANG_TRI"></div>
                            </div>

                            {{-- Nhóm 3: Giấy gói --}}
                            <div class="bom-group giay-goi" data-role="GIAY_GOI" data-unit="tờ">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Giấy gói</h3>
                                        <span id="note-giay-goi">Chuẩn: -- tờ</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-GIAY_GOI">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn giấy gói...">Tìm hoặc chọn giấy gói...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm giấy gói...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-GIAY_GOI"></div>
                            </div>

                            {{-- Nhóm 4: Ruy băng --}}
                            <div class="bom-group ruy-bang" data-role="RUY_BANG" data-unit="mét">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Ruy băng</h3>
                                        <span id="note-ruy-bang">Chuẩn: -- mét</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-RUY_BANG">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn ruy băng...">Tìm hoặc chọn ruy băng...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm ruy băng...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-RUY_BANG"></div>
                            </div>

                            {{-- Nhóm 5: Phụ kiện trang trí --}}
                            <div class="bom-group phu-kien" data-role="PHU_KIEN_TRANG_TRI" data-unit="cái">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Phụ kiện trang trí</h3>
                                        <span id="note-phu-kien">Tối đa: -- món</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-PHU_KIEN_TRANG_TRI">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn phụ kiện...">Tìm hoặc chọn phụ kiện...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm phụ kiện...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-PHU_KIEN_TRANG_TRI"></div>
                            </div>

                            {{-- Nhóm 6: Mút xốp --}}
                            <div class="bom-group mut-xop" data-role="XOP" data-unit="miếng">
                                <div class="bom-group-header">
                                    <div>
                                        <h3>Mút xốp</h3>
                                        <span id="note-mut-xop">Chuẩn: -- miếng</span>
                                    </div>
                                </div>
                                <div class="bom-picker" id="picker-XOP">
                                    <div class="picker-trigger" tabindex="0">
                                        <span class="picker-placeholder" data-default="Tìm hoặc chọn mút xốp...">Tìm hoặc chọn mút xốp...</span>
                                        <i class="bi bi-chevron-down picker-arrow"></i>
                                    </div>
                                    <div class="picker-dropdown">
                                        <div class="picker-search-wrap">
                                            <input type="text" class="picker-search-input" placeholder="Tìm kiếm mút xốp...">
                                        </div>
                                        <div class="picker-list"></div>
                                    </div>
                                </div>
                                <div class="bom-selected" id="selected-XOP"></div>
                            </div>

                        </div>
                    </div>

                </form>
            </div>

            {{-- ─────────────────────────────────────────────────────────────────────
                 TAB 3: BÓ HOA SẴN (GỘP DANH SÁCH + FORM TẠO BÓ HOA THÀNH PHẨM)
            ───────────────────────────────────────────────────────────────────── --}}
            <div class="tab-pane-item d-none" id="tab-bo-hoa-san">
                <div class="premade-page">

                    {{-- 3. HEADER BÓ HOA SẴN --}}
                    <div class="premade-header">
                        <div>
                            <h2>Bó hoa sẵn</h2>
                            <p>Quản lý các bó hoa thành phẩm đã được cắm sẵn và tạo thêm bó mới từ mẫu.</p>
                        </div>
                        <button type="button" class="btn-action-primary" id="btn-toggle-create-premade">
                            <i class="bi bi-plus-lg me-1"></i>Tạo bó hoa
                        </button>
                    </div>

                    {{-- 4. FORM TẠO BÓ HOA SẴN (COLLAPSIBLE PANEL) --}}
                    <div class="premade-create-panel d-none" id="premade-create-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Tạo bó hoa sẵn</h3>
                            <button type="button" class="btn-close" id="btn-close-premade-panel" aria-label="Đóng"></button>
                        </div>

                        <div id="ready-bouquet-message" class="alert d-none py-2 px-3 mb-3 rounded-2" role="status"></div>

                        <form id="form-create-ready-bouquet" action="{{ route('admin.bo-hoa.store') }}" method="POST">
                            @csrf
                            <div class="create-grid">
                                <div class="field">
                                    <label class="form-label small fw-semibold">Mẫu bó hoa <span class="text-danger">*</span></label>
                                    <select name="ma_mau_bo_hoa" id="select-ready-mau" class="form-select" required>
                                        <option value="">-- Chọn mẫu đang hoạt động --</option>
                                        @foreach($mauBoHoas as $m)
                                            @if($m->trang_thai === 'ACTIVE')
                                                <option value="{{ $m->ma_mau_bo_hoa }}"
                                                        data-size="{{ $m->size }}"
                                                        data-kieu="{{ $kieusBo[$m->kieu_bo_hoa] ?? '' }}">
                                                    {{ $m->ten_mau }} · Size {{ $m->size }} ({{ $kieusBo[$m->kieu_bo_hoa] ?? '' }})
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label class="form-label small fw-semibold">Số lượng cần tạo <span class="text-danger">*</span></label>
                                    <input type="number" name="so_luong" id="input-ready-qty" class="form-control" min="1" max="100" value="1" required>
                                </div>

                                <div class="field">
                                    <label class="form-label small fw-semibold">Ngày hết hạn <span class="text-danger">*</span></label>
                                    <input type="date" name="ngay_het_han" id="input-ready-hsd" class="form-control"
                                           value="{{ date('Y-m-d', strtotime('+5 days')) }}"
                                           min="{{ date('Y-m-d') }}" required>
                                </div>

                                <div class="field">
                                    <button type="button" class="btn-action-secondary w-100" id="btn-check-material">
                                        <i class="bi bi-search me-1"></i>Kiểm tra nguyên liệu
                                    </button>
                                </div>
                            </div>

                            {{-- 5 & 6. PREVIEW NGUYÊN LIỆU CẦN DÙNG --}}
                            <div class="material-preview mt-3" id="material-preview-box">
                                <div class="preview-title d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold small text-muted">Bảng preview nguyên liệu cần dùng:</span>
                                    <span id="preview-summary-badge" class="summary-badge status-gray">Chưa chọn mẫu</span>
                                </div>
                                <div class="preview-table-wrap">
                                    <table class="table-preview-materials">
                                        <thead>
                                            <tr>
                                                <th>Thành phần</th>
                                                <th class="text-center">Cần cho 1 bó</th>
                                                <th class="text-center">Cần tổng</th>
                                                <th class="text-center">Khả dụng</th>
                                                <th class="text-end">Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody id="preview-materials-tbody">
                                            <tr>
                                                <td colspan="5" class="text-center py-3 text-muted small">
                                                    Vui lòng chọn mẫu bó hoa và số lượng để xem trước định mức nguyên liệu.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="preview-alert-note" class="alert py-2 px-3 mt-2 mb-0 small d-none"></div>
                            </div>

                            {{-- Form Actions: Hủy & Tạo bó --}}
                            <div class="form-actions mt-3">
                                <button type="button" class="btn-action-secondary" id="btn-cancel-ready-create">
                                    Hủy
                                </button>
                                <button type="submit" class="btn-action-primary" id="btn-submit-ready-create" disabled>
                                    Tạo bó
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- 9. DANH SÁCH BÓ HOA SẴN (ĐỒNG BỘ CHUẨN GIAO DIỆN SẢN PHẨM) --}}
                    <div class="rs-main-card">
                        {{-- BỘ LỌC CHUẨN ROSESHOP --}}
                        <form method="GET" action="{{ route('admin.mau-bo-hoa') }}" class="rs-filter-bar">
                            <input type="hidden" name="page_bo_hoa" value="1">
                            <div class="rs-filter-item">
                                <label class="rs-filter-label">Trạng thái:</label>
                                <select name="trang_thai_bo_hoa" class="rs-filter-select" onchange="this.form.submit()">
                                    <option value="">-- Tất cả trạng thái --</option>
                                    <option value="ACTIVE" @selected(request('trang_thai_bo_hoa') === 'ACTIVE')>Hiện</option>
                                    <option value="DRAFT" @selected(request('trang_thai_bo_hoa') === 'DRAFT')>Ẩn</option>
                                </select>
                            </div>

                            <div class="rs-filter-search-box">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="text" name="q_bo_hoa" value="{{ request('q_bo_hoa') }}" placeholder="Tìm tên bó hoa, mã bó, concept...">
                            </div>

                            <button type="submit" class="rs-btn-filter-submit">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <span>Lọc</span>
                            </button>

                            @if(request()->anyFilled(['q_bo_hoa', 'trang_thai_bo_hoa']))
                                <a href="{{ route('admin.mau-bo-hoa') }}#tab-bo-hoa-san" class="rs-btn-filter-reset" title="Bỏ lọc">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                    <span>Bỏ lọc</span>
                                </a>
                            @endif
                        </form>

                        {{-- TIÊU ĐỀ BẢNG DANH SÁCH CHUẨN --}}
                        <div class="rs-card-title-bar">
                            <div class="rs-card-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="8" y1="6" x2="21" y2="6"/>
                                    <line x1="8" y1="12" x2="21" y2="12"/>
                                    <line x1="8" y1="18" x2="21" y2="18"/>
                                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                                </svg>
                                <span>Danh sách bó hoa sẵn</span>
                                <span class="rs-count-badge">{{ $danhSachBoHoa->total() }}</span>
                            </div>
                        </div>

                        {{-- TABLE DANH SÁCH BÓ HOA SẴN --}}
                        <div class="rs-table-container">
                            <table class="rs-table">
                                <thead>
                                    <tr>
                                        <th style="width: 65px; min-width: 65px; text-align: center;">Ảnh</th>
                                        <th style="min-width: 180px; text-align: left;">Tên bó hoa</th>
                                        <th style="width: 110px; min-width: 110px; text-align: center;">Mẫu nguồn</th>
                                        <th style="width: 115px; min-width: 115px; text-align: center;">Size / Kiểu</th>
                                        <th style="width: 200px; min-width: 200px; text-align: left;">Thời hạn</th>
                                        <th style="width: 105px; min-width: 105px; text-align: center;">Tồn thực tế</th>
                                        <th style="width: 85px; min-width: 85px; text-align: center;">Đang giữ</th>
                                        <th style="width: 90px; min-width: 90px; text-align: center;">Khả dụng</th>
                                        <th style="width: 125px; min-width: 125px; text-align: right;">Giá bán</th>
                                        <th style="width: 95px; min-width: 95px; text-align: center;">Trạng thái</th>
                                        <th style="width: 90px; min-width: 90px; text-align: center;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($danhSachBoHoa as $bo)
                                        @php
                                            $boModel = $bo->bo_dau_tien ?? null;
                                            $mauNguon = $bo->ma_mau_bo_hoa ? $mauMap->get($bo->ma_mau_bo_hoa)?->ten_mau : null;
                                            $anhBo = ($boModel instanceof \App\Models\BoHoa) ? $boController::anh($boModel) : asset('img/default.jpg');
                                            $ngayTao = $bo->created_at ?? $boModel?->created_at;
                                            $ngayHetHan = $bo->ngay_het_han ?? $boModel?->ngay_het_han;
                                            $ngayTaoCarbon = $ngayTao ? \Carbon\Carbon::parse($ngayTao) : null;
                                            if (!$ngayHetHan && $ngayTaoCarbon) {
                                                $ngayHetHan = $ngayTaoCarbon->copy()->addDays(5)->toDateString();
                                            }
                                        @endphp
                                        <tr>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <div class="rs-product-thumb">
                                                    <img src="{{ $anhBo }}"
                                                         alt="{{ $bo->ten_bo_hoa }}"
                                                         class="rs-product-thumb-img"
                                                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'rs-product-thumb-placeholder\'><i class=\'bi bi-flower1\'></i></div>';">
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="d-block text-dark text-nowrap" style="font-size:13.5px;">{{ $bo->ten_bo_hoa }}</strong>
                                                <small class="text-muted text-nowrap" style="font-size:12px;">#{{ $bo->ma_bo_hoa }} · {{ $concepts[$bo->concept] ?? $bo->concept }}</small>
                                            </td>
                                            <td style="text-align: center;">
                                                @if($mauNguon)
                                                    <span class="badge bg-light text-dark border text-nowrap">{{ $mauNguon }}</span>
                                                @else
                                                    <span class="text-muted small text-nowrap">Tự thiết kế</span>
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge bg-light text-dark border">Size {{ $bo->size }}</span>
                                                <small class="text-muted d-block text-nowrap mt-0.5" style="font-size:11.5px;">{{ $kieusBo[$bo->kieu_bo_hoa] ?? $bo->kieu_bo_hoa }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1" style="font-size:12px;">
                                                    <div class="text-nowrap" title="Ngày cắm/tạo bó hoa">
                                                        <span class="text-muted"><i class="bi bi-calendar-event me-1"></i>Tạo:</span>
                                                        <span class="fw-semibold text-dark">{{ $ngayTao ? \Carbon\Carbon::parse($ngayTao)->format('d/m/Y') : '—' }}</span>
                                                    </div>
                                                    <div class="text-nowrap">
                                                        <span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>HSD:</span>
                                                        @if($ngayHetHan)
                                                            @php
                                                                $hsdBo = \Carbon\Carbon::parse($ngayHetHan)->startOfDay();
                                                                $today = \Carbon\Carbon::today();
                                                                $diffBo = $today->diffInDays($hsdBo, false);
                                                            @endphp
                                                            @if($diffBo < 0)
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold text-nowrap" style="font-size:11px; padding:2px 6px;" title="Bó hoa đã quá hạn {{ abs($diffBo) }} ngày">
                                                                    {{ $hsdBo->format('d/m/Y') }} (Quá hạn)
                                                                </span>
                                                            @elseif($diffBo <= 2)
                                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold text-nowrap" style="font-size:11px; padding:2px 6px;" title="Sắp hết hạn trong {{ $diffBo }} ngày">
                                                                    {{ $hsdBo->format('d/m/Y') }} (Còn {{ $diffBo }}n)
                                                                </span>
                                                            @else
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold text-nowrap" style="font-size:11px; padding:2px 6px;" title="Hạn sử dụng còn {{ $diffBo }} ngày">
                                                                    {{ $hsdBo->format('d/m/Y') }} (Còn {{ $diffBo }}n)
                                                                </span>
                                                            @endif
                                                        @else
                                                            <span class="text-muted fst-italic">—</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="text-align: center; font-family: monospace;">
                                                <strong>{{ $bo->so_luong }}</strong>
                                            </td>
                                            <td style="text-align: center; font-family: monospace; color: #64748b;">
                                                0
                                            </td>
                                            <td style="text-align: center; font-family: monospace;" class="fw-bold text-success">
                                                {{ $bo->so_luong }}
                                            </td>
                                            <td style="text-align: right;" class="fw-bold text-success">
                                                {{ number_format($bo->gia_ban, 0, ',', '.') }} đ
                                            </td>
                                            <td style="text-align: center;">
                                                @if($bo->trang_thai === 'ACTIVE')
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Hiện</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Ẩn</span>
                                                @endif
                                            </td>
                                            <td style="text-align: center; white-space: nowrap;">
                                                <div class="rs-action-group">
                                                    {{-- Nút Xem chi tiết giá --}}
                                                    <button type="button"
                                                            class="rs-action-btn"
                                                            data-bo-chitiet-gia="{{ route('admin.bo-hoa.chi-tiet-gia', $bo->ma_bo_hoa) }}"
                                                            title="Xem chi tiết giá bó hoa">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <circle cx="12" cy="12" r="10"/>
                                                            <line x1="12" y1="16" x2="12" y2="12"/>
                                                            <line x1="12" y1="8" x2="12.01" y2="8"/>
                                                        </svg>
                                                    </button>

                                                    {{-- Nút Báo hỏng --}}
                                                    <button type="button"
                                                            class="rs-action-btn danger"
                                                            data-bo-baohong="{{ route('admin.bo-hoa.bao-hong', $bo->ma_bo_hoa) }}"
                                                            data-ten="{{ $bo->ten_bo_hoa }}"
                                                            data-sl="{{ $bo->so_luong }}"
                                                            {{ $bo->so_luong < 1 ? 'disabled style=opacity:0.4;cursor:not-allowed;' : '' }}
                                                            title="Báo hỏng bó hoa">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                            <line x1="12" y1="9" x2="12" y2="13"/>
                                                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center py-5 text-muted">
                                                <i class="bi bi-flower1 fs-2 d-block mb-2 text-secondary"></i>
                                                <p class="mb-2 fw-semibold">Chưa có bó hoa sẵn nào trong kho.</p>
                                                <button type="button" class="btn btn-sm btn-action-primary" id="btn-empty-create-premade">
                                                    <i class="bi bi-plus-lg me-1"></i>Tạo bó hoa đầu tiên
                                                </button>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- FOOTER PHÂN TRANG ĐỒNG BỘ CHUẨN ROSESHOP --}}
                        @if($danhSachBoHoa->total() > 0)
                            <div class="rs-table-footer">
                                <div class="rs-footer-left">
                                    <div class="rs-per-page-inline">
                                        <span class="rs-per-page-label">Số hàng mỗi trang</span>
                                        <select class="rs-per-page-select" onchange="location = this.value;">
                                            @foreach([10, 20, 50, 100] as $perPage)
                                                <option value="{{ request()->fullUrlWithQuery(['per_page_bo_hoa' => $perPage, 'page_bo_hoa' => 1]) }}#tab-bo-hoa-san" {{ request('per_page_bo_hoa', 10) == $perPage ? 'selected' : '' }}>
                                                    {{ $perPage }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="rs-records-count">
                                        {{ $danhSachBoHoa->firstItem() ?? 0 }}-{{ $danhSachBoHoa->lastItem() ?? 0 }} / {{ $danhSachBoHoa->total() }} bản ghi
                                    </div>
                                </div>
                                <div class="rs-footer-pagination">
                                    <ul class="rs-pagination">
                                        @if ($danhSachBoHoa->onFirstPage())
                                            <li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>
                                        @else
                                            <li class="page-item"><a class="page-link" href="{{ $danhSachBoHoa->previousPageUrl() }}#tab-bo-hoa-san" rel="prev">&lsaquo;</a></li>
                                        @endif

                                        @php
                                            $currentPage = $danhSachBoHoa->currentPage();
                                            $lastPage = $danhSachBoHoa->lastPage();
                                            $pages = [];

                                            if ($lastPage <= 3) {
                                                for ($i = 1; $i <= $lastPage; $i++) {
                                                    $pages[] = $i;
                                                }
                                            } else {
                                                if ($currentPage == 1) {
                                                    $pages = [1, '...', $lastPage];
                                                } elseif ($currentPage == $lastPage) {
                                                    $pages = [1, '...', $lastPage];
                                                } else {
                                                    $pages[] = 1;
                                                    if ($currentPage > 2) {
                                                        $pages[] = '...';
                                                    }
                                                    $pages[] = $currentPage;
                                                    if ($currentPage < $lastPage - 1) {
                                                        $pages[] = '...';
                                                    }
                                                    $pages[] = $lastPage;
                                                }
                                            }
                                        @endphp

                                        @foreach ($pages as $p)
                                            @if ($p === '...')
                                                <li class="page-item disabled"><span class="page-link ellipsis">&hellip;</span></li>
                                            @elseif ($p == $currentPage)
                                                <li class="page-item active"><span class="page-link" style="background-color: #EC3D8F !important; background: #EC3D8F !important; border-color: #EC3D8F !important; color: #ffffff !important; font-weight: 600 !important; box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;">{{ $p }}</span></li>
                                            @else
                                                <li class="page-item"><a class="page-link" href="{{ $danhSachBoHoa->url($p) }}#tab-bo-hoa-san">{{ $p }}</a></li>
                                            @endif
                                        @endforeach

                                        @if ($danhSachBoHoa->hasMorePages())
                                            <li class="page-item"><a class="page-link" href="{{ $danhSachBoHoa->nextPageUrl() }}#tab-bo-hoa-san" rel="next">&rsaquo;</a></li>
                                        @else
                                            <li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

{{-- MODAL CHI TIẾT GIÁ BÓ HOA --}}
<div class="modal fade" id="modalChiTietGiaBo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold">Chi tiết giá: <span id="ctg_ten">—</span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div class="mb-2 text-muted small">
                    #<span id="ctg_ma">—</span> · Size: <strong id="ctg_size">—</strong> · Kiểu: <strong id="ctg_kieu">—</strong>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.84rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Nguyên liệu</th>
                                <th>Vai trò</th>
                                <th class="text-end">SL</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody id="ctg_nguyen_lieu"></tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="4" class="text-end">Tổng giá gốc nguyên liệu:</td>
                                <td class="text-end text-primary" id="ctg_tong_nl">0 đ</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="border rounded p-2 bg-light mb-2 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Phụ phí size (<span id="ctg_size_label">—</span>):</span>
                        <span id="ctg_pp_size">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Phụ phí kiểu dáng (<span id="ctg_kieu_label">—</span>):</span>
                        <span id="ctg_pp_kieu">0 đ</span>
                    </div>
                    <div class="d-flex justify-content-between pt-1 border-top fw-bold text-primary">
                        <span>Tổng phụ phí:</span>
                        <span id="ctg_pp_tong">0 đ</span>
                    </div>
                </div>

                <div class="p-2 rounded bg-success-subtle text-success fw-bold d-flex justify-content-between align-items-center">
                    <span>Giá bán mỗi bó:</span>
                    <span class="fs-6" id="ctg_gia_ban">0 đ</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL BÁO CÁO BÓ HOA HỎNG --}}
<div class="modal fade" id="modalHangHongBo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header bg-danger text-white py-2 px-3">
                <h6 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-1"></i>Báo Cáo Bó Hoa Hỏng</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formHangHongBo">
                @csrf
                <input type="hidden" id="bo_hh_url">
                <div class="modal-body py-3 px-3">
                    <div class="mb-2">
                        <span class="text-muted small">Bó hoa:</span> <strong id="bo_hh_ten">—</strong>
                        <div class="small">Tồn khả dụng: <span class="badge bg-secondary" id="bo_hh_sl">0</span></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Số lượng hỏng <span class="text-danger">*</span></label>
                        <input type="number" name="so_luong_hong" id="bo_hh_so_luong" class="form-control" min="1" required>
                        <div class="text-danger small mt-1" id="bo_hh_sl_error" style="display:none;">Vượt quá số lượng hiện có!</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Lý do <span class="text-danger">*</span></label>
                        <input type="text" name="ly_do" id="bo_hh_ly_do" class="form-control" required placeholder="Hoa héo, gãy cành...">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Ghi chú</label>
                        <textarea name="ghi_chu" id="bo_hh_ghi_chu" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="bo_hh_submit" disabled>Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    // ── Dữ liệu khởi tạo từ Backend ──────────────────────────
    const flowerProducts  = @json($flowerProducts);
    const giayGoiProducts = @json($giayGoiProducts);
    const ruyBangProducts = @json($ruyBangProducts);
    const phuKienProducts = @json($phuKienProducts);
    const xopProducts     = @json($xopProducts);

    const productsByGroup = {
        'HOA_CHINH':          flowerProducts,
        'HOA_TRANG_TRI':      flowerProducts,
        'GIAY_GOI':           giayGoiProducts,
        'RUY_BANG':           ruyBangProducts,
        'PHU_KIEN_TRANG_TRI': phuKienProducts,
        'XOP':                xopProducts
    };

    const sizesData = @json($sizesBo);
    const kieusData = @json($kieusBo);
    const fixedRules = {
        giayGoi:  @json($giayGoiQty),
        ruyBang:  @json($ruyBangQty),
        xop:      @json($xopQty),
        phuKien:  @json($phuKienMax)
    };

    const templatesBomData = @json($mauBoHoaData);
    const materialStockMap = @json($tonKhoKhaDungMap);
    const existingTemplates = @json($mauBoHoas->map(fn($m) => ['id' => $m->ma_mau_bo_hoa, 'ten' => $m->ten_mau]));
    const existingTemplateNames = existingTemplates.map(t => t.ten);

    const csrf = '{{ csrf_token() }}';
    const msgEl = document.getElementById('bo-message');

    function tell(text, ok = false) {
        msgEl.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger') + ' py-2 px-3 mb-3 font-semibold rounded-2';
        msgEl.textContent = text;
        msgEl.classList.remove('d-none');
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function api(url, method = 'GET', data = null) {
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
        const opts = { method, headers };
        if (data) opts.body = data;
        const res = await fetch(url, opts);
        let result;
        try { result = await res.json(); }
        catch { throw new Error('Phản hồi không hợp lệ từ máy chủ.'); }
        if (!res.ok || result.success === false) {
            const err = new Error(result.message || 'Thao tác không thành công.');
            err.errors = result.errors || {};
            throw err;
        }
        return result;
    }

    const money = n => Number(n || 0).toLocaleString('vi-VN') + ' đ';

    // =========================================================================
    // 2. CẤU HÌNH PHỤ PHÍ
    // =========================================================================
    const feeSection       = document.getElementById('fee-section');
    const feeHeader        = document.getElementById('fee-section-header');
    const feeToggleBtn     = document.getElementById('btn-toggle-fee');
    const feeToggleText    = document.getElementById('fee-toggle-text');
    const feeForm          = document.getElementById('form-fee-quick');
    const feeSelectLoai    = document.getElementById('fee-select-loai');
    const feeSelectVal     = document.getElementById('fee-select-giatri');
    const feeInputTien     = document.getElementById('fee-input-sotien');

    function toggleFeeSection(e) {
        if (e && e.target && e.target.closest('button') && e.target.closest('button') !== feeToggleBtn) return;
        const isCollapsed = feeSection.classList.toggle('collapsed');
        if (feeToggleText) {
            feeToggleText.textContent = isCollapsed ? 'Mở rộng' : 'Thu gọn';
        }
        try {
            localStorage.setItem('rose_fee_section_collapsed', isCollapsed ? '1' : '0');
        } catch (_) {}
    }

    if (feeHeader) {
        feeHeader.addEventListener('click', toggleFeeSection);
    }
    try {
        if (localStorage.getItem('rose_fee_section_collapsed') === '1') {
            feeSection.classList.add('collapsed');
            if (feeToggleText) feeToggleText.textContent = 'Mở rộng';
        }
    } catch (_) {}

    function updateFeeValOptions(selected = null) {
        feeSelectVal.replaceChildren();
        if (feeSelectLoai.value === 'SIZE') {
            Object.keys(sizesData).forEach(k => {
                const label = k === 'SPECIAL' ? 'Special' : k;
                const opt = new Option(`${label} (${sizesData[k]} bông)`, k);
                if (selected === k) opt.selected = true;
                feeSelectVal.add(opt);
            });
        } else {
            Object.keys(kieusData).forEach(k => {
                const opt = new Option(kieusData[k], k);
                if (selected === k) opt.selected = true;
                feeSelectVal.add(opt);
            });
        }
    }
    feeSelectLoai.addEventListener('change', () => updateFeeValOptions());
    updateFeeValOptions();

    // Nút Sửa trong 2 bảng nhỏ phụ phí
    document.querySelectorAll('.btn-trigger-fee-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const loai = btn.dataset.loai;
            const giatri = btn.dataset.giatri;
            const phi = btn.dataset.phi;

            // Tự động mở rộng nếu đang thu gọn
            if (feeSection && feeSection.classList.contains('collapsed')) {
                feeSection.classList.remove('collapsed');
                if (feeToggleText) feeToggleText.textContent = 'Thu gọn';
                try { localStorage.setItem('rose_fee_section_collapsed', '0'); } catch (_) {}
            }

            feeSelectLoai.value = loai;
            updateFeeValOptions(giatri);
            feeInputTien.value = phi;
            feeInputTien.focus();
            feeForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    feeForm.onsubmit = async (e) => {
        e.preventDefault();
        try {
            const fd = new FormData(feeForm);
            const res = await api(feeForm.action, 'POST', fd);
            tell(res.message || 'Đã lưu cấu hình phụ phí.', true);
            setTimeout(() => location.reload(), 500);
        } catch (err) {
            tell(err.message);
        }
    };

    // =========================================================================
    // 4. TABS (TEXT + UNDERLINE)
    // =========================================================================
    const tabBtns = document.querySelectorAll('.bouquet-tabs .tab-btn');
    const tabPanes = document.querySelectorAll('.tab-container-content .tab-pane-item');

    function activateTab(targetSelector) {
        tabBtns.forEach(b => {
            if (b.dataset.target === targetSelector) b.classList.add('active');
            else b.classList.remove('active');
        });
        tabPanes.forEach(p => p.classList.add('d-none'));
        const targetPane = document.querySelector(targetSelector);
        if (targetPane) targetPane.classList.remove('d-none');
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            activateTab(btn.dataset.target);
        });
    });

    // Tự động kích hoạt tab nếu URL có param hoặc hash liên quan
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page_bo_hoa') || urlParams.has('q_bo_hoa') || urlParams.has('trang_thai_bo_hoa') || window.location.hash === '#tab-bo-hoa-san' || window.location.hash === '#khoi-bo-hoa') {
        activateTab('#tab-bo-hoa-san');
    } else if (window.location.hash && document.querySelector(window.location.hash)) {
        activateTab(window.location.hash);
    }

    // =========================================================================
    // 24. TAB DANH SÁCH MẪU: SEARCH & FILTER
    // =========================================================================
    // =========================================================================
    // 24. TAB DANH SÁCH MẪU: SEARCH, FILTER & PHÂN TRANG CHUẨN ROSESHOP
    // =========================================================================
    const searchMauName      = document.getElementById('search-mau-name');
    const filterMauStatus    = document.getElementById('filter-mau-status');
    const btnSearchMau       = document.getElementById('btn-search-mau');
    const mauRows            = Array.from(document.querySelectorAll('#tbody-mau-list .mau-row'));
    const mauPerPageSelect   = document.getElementById('mau-per-page-select');
    const mauRecordsCount    = document.getElementById('mau-records-count');
    const mauPaginationList  = document.getElementById('mau-pagination-list');
    const mauTotalCountBadge = document.getElementById('mau-total-count');

    let currentMauPage = 1;

    function renderMauPagination(visibleRows) {
        const total = visibleRows.length;
        const perPage = parseInt(mauPerPageSelect?.value || 10, 10);
        const totalPages = Math.max(1, Math.ceil(total / perPage));

        if (currentMauPage > totalPages) currentMauPage = 1;
        if (currentMauPage < 1) currentMauPage = 1;

        mauRows.forEach(r => r.style.display = 'none');

        const startIdx = (currentMauPage - 1) * perPage;
        const endIdx   = Math.min(startIdx + perPage, total);

        for (let i = startIdx; i < endIdx; i++) {
            if (visibleRows[i]) {
                visibleRows[i].style.display = '';
            }
        }

        if (mauRecordsCount) {
            if (total === 0) {
                mauRecordsCount.textContent = '0 / 0 bản ghi';
            } else {
                mauRecordsCount.textContent = `${startIdx + 1}-${endIdx} / ${total} bản ghi`;
            }
        }

        if (mauTotalCountBadge) {
            mauTotalCountBadge.textContent = total;
        }

        if (!mauPaginationList) return;
        mauPaginationList.replaceChildren();

        if (total === 0) return;

        // Nút Prev
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentMauPage === 1 ? 'disabled' : ''}`;
        if (currentMauPage === 1) {
            prevLi.innerHTML = `<span class="page-link">&lsaquo;</span>`;
        } else {
            const prevA = document.createElement('a');
            prevA.className = 'page-link';
            prevA.href = 'javascript:void(0)';
            prevA.innerHTML = '&lsaquo;';
            prevA.addEventListener('click', () => {
                currentMauPage--;
                applyMauFilter(false);
            });
            prevLi.appendChild(prevA);
        }
        mauPaginationList.appendChild(prevLi);

        // Danh sách số trang
        let pages = [];
        if (totalPages <= 3) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            if (currentMauPage === 1 || currentMauPage === totalPages) {
                pages = [1, '...', totalPages];
            } else {
                pages.push(1);
                if (currentMauPage > 2) pages.push('...');
                pages.push(currentMauPage);
                if (currentMauPage < totalPages - 1) pages.push('...');
                pages.push(totalPages);
            }
        }

        pages.forEach(p => {
            const li = document.createElement('li');
            if (p === '...') {
                li.className = 'page-item disabled';
                li.innerHTML = '<span class="page-link ellipsis">&hellip;</span>';
            } else if (p === currentMauPage) {
                li.className = 'page-item active';
                li.innerHTML = `<span class="page-link" style="background-color: #EC3D8F !important; background: #EC3D8F !important; border-color: #EC3D8F !important; color: #ffffff !important; font-weight: 600 !important; box-shadow: 0 1px 3px rgba(236, 61, 143, 0.25) !important;">${p}</span>`;
            } else {
                li.className = 'page-item';
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = 'javascript:void(0)';
                a.textContent = p;
                a.addEventListener('click', () => {
                    currentMauPage = p;
                    applyMauFilter(false);
                });
                li.appendChild(a);
            }
            mauPaginationList.appendChild(li);
        });

        // Nút Next
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentMauPage === totalPages ? 'disabled' : ''}`;
        if (currentMauPage === totalPages) {
            nextLi.innerHTML = `<span class="page-link">&rsaquo;</span>`;
        } else {
            const nextA = document.createElement('a');
            nextA.className = 'page-link';
            nextA.href = 'javascript:void(0)';
            nextA.innerHTML = '&rsaquo;';
            nextA.addEventListener('click', () => {
                currentMauPage++;
                applyMauFilter(false);
            });
            nextLi.appendChild(nextA);
        }
        mauPaginationList.appendChild(nextLi);
    }

    function applyMauFilter(resetPage = true) {
        if (resetPage) currentMauPage = 1;
        const q = (searchMauName?.value || '').trim().toLowerCase();
        const st = filterMauStatus?.value || '';

        const visibleRows = mauRows.filter(r => {
            const rName = r.dataset.name || '';
            const rStatus = r.dataset.status || '';
            const matchQ = !q || rName.includes(q);
            const matchSt = !st || rStatus === st;
            return matchQ && matchSt;
        });

        renderMauPagination(visibleRows);
    }

    searchMauName?.addEventListener('input', () => applyMauFilter(true));
    filterMauStatus?.addEventListener('change', () => applyMauFilter(true));
    btnSearchMau?.addEventListener('click', () => applyMauFilter(true));
    mauPerPageSelect?.addEventListener('change', () => applyMauFilter(true));

    applyMauFilter(true);

    // Thao tác đổi trạng thái / xóa mẫu
    document.querySelectorAll('[data-bo-action]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) return;
            if (btn.dataset.confirm && !confirm(btn.dataset.confirm)) return;
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.set('_method', btn.dataset.method || 'POST');
                const r = await api(btn.dataset.boAction, 'POST', fd);
                tell(r.message || 'Thành công!', true);
                setTimeout(() => location.reload(), 500);
            } catch (err) {
                tell(err.message);
                btn.disabled = false;
            }
        });
    });

    // =========================================================================
    // 5 & 6. TAB TẠO MẪU: XỬ LÝ ẢNH & THÔNG TIN CƠ BẢN
    // =========================================================================
    const inputTplImg   = document.getElementById('input-tpl-image');
    const imgTplPreview = document.getElementById('tpl-img-preview');
    const imgTplHolder  = document.getElementById('tpl-img-placeholder');

    inputTplImg?.addEventListener('change', () => {
        const file = inputTplImg.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                imgTplPreview.src = e.target.result;
                imgTplPreview.classList.remove('d-none');
                imgTplHolder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            imgTplPreview.classList.add('d-none');
            imgTplHolder.classList.remove('d-none');
        }
    });

    const selectTplSize = document.getElementById('select-tpl-size');
    const selectTplKieu = document.getElementById('select-tpl-kieu');
    const formCreateTpl = document.getElementById('form-create-template');
    const btnResetTpl   = document.getElementById('btn-reset-template');
    const btnSubmitTpl  = document.getElementById('btn-submit-template');
    const msgCreateTpl  = document.getElementById('template-create-message');

    function showTplMessage(text, ok = false) {
        if (msgCreateTpl) {
            msgCreateTpl.className = 'alert ' + (ok ? 'alert-success' : 'alert-danger') + ' py-2 px-3 mb-3 rounded-2';
            msgCreateTpl.innerHTML = (ok ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<i class="bi bi-exclamation-triangle-fill me-1"></i>') + text;
            msgCreateTpl.classList.remove('d-none');
            msgCreateTpl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        tell(text, ok);
    }

    // 22. KHI ĐỔI SIZE: Cập nhật nhãn động từng nhóm realtime
    selectTplSize?.addEventListener('change', () => {
        const opt = selectTplSize.options[selectTplSize.selectedIndex];
        const s = opt?.value;
        const labelSize = s === 'SPECIAL' ? 'Special' : s;

        if (s && fixedRules.giayGoi[s]) {
            document.getElementById('note-hoa-chinh').textContent    = `Có thể chọn nhiều loại (≥ 1 bông, chuẩn Size ${labelSize}: ${sizesData[s]} bông)`;
            document.getElementById('note-hoa-trang-tri').textContent= `Hoa phụ / hoa đệm theo size (chuẩn Size ${labelSize}: ${sizesData[s]} bông)`;
            document.getElementById('note-giay-goi').textContent     = `Chuẩn Size ${labelSize}: ${fixedRules.giayGoi[s]} tờ`;
            document.getElementById('note-ruy-bang').textContent    = `Chuẩn Size ${labelSize}: ${fixedRules.ruyBang[s]} mét`;
            document.getElementById('note-phu-kien').textContent    = `Tối đa Size ${labelSize}: ${fixedRules.phuKien[s]} món`;
            document.getElementById('note-mut-xop').textContent     = `Chuẩn Size ${labelSize}: ${fixedRules.xop[s]} miếng`;
        } else {
            document.getElementById('note-hoa-chinh').textContent    = 'Có thể chọn nhiều loại (≥ 1 bông)';
            document.getElementById('note-hoa-trang-tri').textContent= 'Hoa phụ / hoa đệm theo size';
            document.getElementById('note-giay-goi').textContent     = 'Chuẩn: -- tờ';
            document.getElementById('note-ruy-bang').textContent    = 'Chuẩn: -- mét';
            document.getElementById('note-phu-kien').textContent    = 'Tối đa: -- món';
            document.getElementById('note-mut-xop').textContent     = 'Chuẩn: -- miếng';
        }
        recalcSummary();
    });

    selectTplKieu?.addEventListener('change', recalcSummary);
    document.getElementById('select-tpl-concept')?.addEventListener('change', recalcSummary);
    const inputTplName    = document.getElementById('input-tpl-name');
    const tplNameDropdown = document.getElementById('tpl-name-dropdown');
    const tplNameList     = document.getElementById('tpl-name-list');
    const tplNameFeedback = document.getElementById('tpl-name-feedback');

    function normalizeName(str) {
        if (!str) return '';
        return str.trim().replace(/\s+/g, ' ').toLowerCase();
    }

    function renderTplNameDropdown(query = '') {
        if (!tplNameList) return;
        tplNameList.replaceChildren();

        const q = normalizeName(query);
        const filtered = existingTemplateNames.filter(name => {
            return !q || normalizeName(name).includes(q);
        });

        if (filtered.length === 0) {
            const noRes = document.createElement('div');
            noRes.className = 'text-muted small p-2 text-center';
            noRes.textContent = 'Không có tên mẫu trùng hoặc tương tự';
            tplNameList.appendChild(noRes);
            return;
        }

        filtered.forEach(name => {
            const item = document.createElement('div');
            const isMatch = q && normalizeName(name) === q;
            item.className = 'tpl-name-item' + (isMatch ? ' match-highlight' : '');
            item.innerHTML = `
                <span>${name}</span>
                <span class="tpl-name-badge-exists">Đã có</span>
            `;
            item.onclick = (e) => {
                e.stopPropagation();
                inputTplName.value = name;
                checkTplNameDuplicate();
                tplNameDropdown.classList.add('d-none');
            };
            tplNameList.appendChild(item);
        });
    }

    function checkTplNameDuplicate() {
        if (!inputTplName) return false;
        const val = inputTplName.value || '';
        const norm = normalizeName(val);

        if (!norm) {
            inputTplName.classList.remove('input-invalid-border');
            if (tplNameFeedback) tplNameFeedback.classList.add('d-none');
            recalcSummary();
            return false;
        }

        const isDuplicate = existingTemplateNames.some(name => normalizeName(name) === norm);
        if (isDuplicate) {
            inputTplName.classList.add('input-invalid-border');
            if (tplNameFeedback) {
                tplNameFeedback.textContent = 'Tên mẫu đã tồn tại. Vui lòng chọn tên khác.';
                tplNameFeedback.classList.remove('d-none');
            }
        } else {
            inputTplName.classList.remove('input-invalid-border');
            if (tplNameFeedback) tplNameFeedback.classList.add('d-none');
        }

        recalcSummary();
        return isDuplicate;
    }

    if (inputTplName) {
        inputTplName.addEventListener('focus', () => {
            renderTplNameDropdown(inputTplName.value);
            tplNameDropdown?.classList.remove('d-none');
        });

        inputTplName.addEventListener('input', () => {
            renderTplNameDropdown(inputTplName.value);
            tplNameDropdown?.classList.remove('d-none');
            checkTplNameDuplicate();
        });

        inputTplName.addEventListener('blur', () => {
            setTimeout(() => {
                tplNameDropdown?.classList.add('d-none');
                checkTplNameDuplicate();
            }, 200);
        });

        inputTplName.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                tplNameDropdown?.classList.add('d-none');
            }
        });
    }

    // Đóng dropdown tên mẫu khi click ra ngoài
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#tpl-name-group')) {
            tplNameDropdown?.classList.add('d-none');
        }
    });

    // =========================================================================
    // 7..14. THÀNH PHẦN BOM: SEARCHABLE PICKER & SELECTED ITEMS SCROLL
    // =========================================================================
    // Quản lý dữ liệu item đã chọn: { [role]: [ { id, name, qty, available, unit }, ... ] }
    const bomSelected = {
        'HOA_CHINH':          [],
        'HOA_TRANG_TRI':      [],
        'GIAY_GOI':           [],
        'RUY_BANG':           [],
        'PHU_KIEN_TRANG_TRI': [],
        'XOP':                []
    };

    const groupUnits = {
        'HOA_CHINH':          'cành',
        'HOA_TRANG_TRI':      'cành',
        'GIAY_GOI':           'tờ',
        'RUY_BANG':           'mét',
        'PHU_KIEN_TRANG_TRI': 'cái',
        'XOP':                'miếng'
    };

    // 13 & 14. Hiển thị item đã chọn (compact, 2 cột desktop, scroll bên trong max-height 150px)
    function renderGroupSelected(role) {
        const container = document.getElementById(`selected-${role}`);
        const items = bomSelected[role] || [];
        const groupEl = document.querySelector(`.bom-group[data-role="${role}"]`);
        const fallbackUnit = groupEl?.dataset?.unit || groupUnits[role] || 'cái';

        // Cập nhật text hiển thị trên nút trigger của picker
        const pickerBox = document.getElementById(`picker-${role}`);
        const triggerText = pickerBox?.querySelector('.picker-placeholder');
        if (triggerText) {
            const defaultText = triggerText.getAttribute('data-default') || 'Chọn nguyên liệu...';
            if (items.length === 0) {
                triggerText.textContent = defaultText;
                triggerText.classList.remove('picker-selected-text');
            } else {
                const names = items.map(x => `${x.name} (${x.qty} ${x.unit || fallbackUnit})`).join(', ');
                triggerText.textContent = names;
                triggerText.classList.add('picker-selected-text');
            }
        }

        container.replaceChildren();

        // 21. Empty state
        if (items.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'empty-state-chip';
            empty.textContent = 'Chưa chọn thành phần';
            container.appendChild(empty);
            recalcSummary();
            return;
        }

        items.forEach((item, idx) => {
            const chip = document.createElement('div');
            chip.className = 'selected-chip';
            const itemUnit = item.unit || fallbackUnit;
            chip.innerHTML = `
                <div class="chip-info">
                    <span class="chip-name" title="${item.name}">${item.name}</span>
                    <span class="chip-stock-info">Kho khả dụng: ${item.available} ${itemUnit}</span>
                </div>
                <div class="chip-qty-wrap">
                    <input type="number" class="chip-qty-input" value="${item.qty}" min="1" max="${item.available}">
                    <span class="chip-unit">${itemUnit}</span>
                </div>
                <button type="button" class="btn-chip-remove" title="Xóa">×</button>
            `;

            const inputQty  = chip.querySelector('.chip-qty-input');
            const btnRemove = chip.querySelector('.btn-chip-remove');

            const syncQtyChange = (newVal) => {
                item.qty = newVal;
                if (triggerText) {
                    triggerText.textContent = items.map(x => `${x.name} (${x.qty} ${x.unit || fallbackUnit})`).join(', ');
                }
                recalcSummary();
            };

            inputQty.oninput = () => {
                let n = parseInt(inputQty.value);
                if (n > item.available) {
                    showTplMessage(`Chỉ còn ${item.available} ${itemUnit} khả dụng cho "${item.name}".`);
                    inputQty.value = item.available;
                    n = item.available;
                } else if (n >= 1) {
                    // valid
                } else {
                    return;
                }
                syncQtyChange(n);
            };

            inputQty.onchange = () => {
                let n = parseInt(inputQty.value);
                if (isNaN(n) || n < 1) n = 1;
                if (n > item.available) {
                    showTplMessage(`Chỉ còn ${item.available} ${itemUnit} khả dụng cho "${item.name}".`);
                    n = item.available;
                }
                inputQty.value = n;
                syncQtyChange(n);
            };

            btnRemove.onclick = () => {
                bomSelected[role].splice(idx, 1);
                renderGroupSelected(role);
                // Cập nhật lại dropdown để kích hoạt lại item vừa xóa
                updatePickerList(role);
                if (role === 'HOA_CHINH') updatePickerList('HOA_TRANG_TRI');
                if (role === 'HOA_TRANG_TRI') updatePickerList('HOA_CHINH');
            };

            container.appendChild(chip);
        });

        recalcSummary();
    }

    // 9, 10, 11, 12. Searchable Multi-Select Picker cho mỗi nhóm
    function updatePickerList(role) {
        const pickerBox = document.getElementById(`picker-${role}`);
        if (!pickerBox) return;

        const listEl = pickerBox.querySelector('.picker-list');
        const searchInput = pickerBox.querySelector('.picker-search-input');
        const q = (searchInput?.value || '').trim().toLowerCase();
        const products = productsByGroup[role] || [];
        const selectedIds = (bomSelected[role] || []).map(x => x.id);

        // Danh sách ID hoa đã chọn ở nhóm đối ứng (Rule 18: Hoa chính và Hoa trang trí không trùng nhau)
        const hoaChinhIds = (bomSelected['HOA_CHINH'] || []).map(x => x.id);
        const hoaTTIds    = (bomSelected['HOA_TRANG_TRI'] || []).map(x => x.id);

        listEl.replaceChildren();

        const filtered = products.filter(p => {
            return !q || p.ten_san_pham.toLowerCase().includes(q) || String(p.ma_san_pham).includes(q);
        });

        if (filtered.length === 0) {
            const noRes = document.createElement('div');
            noRes.className = 'text-muted small p-2 text-center';
            noRes.textContent = 'Không tìm thấy nguyên liệu';
            listEl.appendChild(noRes);
            return;
        }

        filtered.forEach(p => {
            const isAlreadySelected = selectedIds.includes(p.ma_san_pham);
            const isDuplicateFlower = (role === 'HOA_TRANG_TRI' && hoaChinhIds.includes(p.ma_san_pham))
                                   || (role === 'HOA_CHINH' && hoaTTIds.includes(p.ma_san_pham));
            const available = p.ton_kha_dung !== undefined ? p.ton_kha_dung : (materialStockMap[p.ma_san_pham] ?? 0);
            const unit = p.don_vi_tinh_label || p.don_vi_tinh_text || (groupUnits[role] || 'cái');
            const isOutOfStock = available <= 0;

            const itemEl = document.createElement('div');

            if (isOutOfStock) {
                itemEl.className = 'picker-item out-of-stock disabled' + (isAlreadySelected ? ' selected-disabled' : '');
                itemEl.title = `${p.ten_san_pham} - Hết hàng khả dụng`;
                itemEl.innerHTML = `
                    <div class="picker-item-left">
                        <span class="picker-item-name text-muted">${p.ten_san_pham} ${isAlreadySelected ? '✓' : ''}</span>
                        <span class="stock-badge badge-out-of-stock">Hết hàng</span>
                    </div>
                    <small class="text-muted">${money(p.gia_ban_hien_tai)}</small>
                `;
            } else if (isDuplicateFlower) {
                const otherGroup = role === 'HOA_TRANG_TRI' ? 'hoa chính' : 'hoa trang trí';
                itemEl.className = 'picker-item disabled selected-disabled';
                itemEl.title = `${p.ten_san_pham} - Đã chọn ở ${otherGroup}`;
                itemEl.innerHTML = `
                    <div class="picker-item-left">
                        <span class="picker-item-name text-muted">${p.ten_san_pham}</span>
                        <span class="stock-badge" style="background:#F3F4F6;color:#6B7280;border:1px solid #D1D5DB;">Đã chọn ở ${otherGroup}</span>
                    </div>
                    <small class="text-muted">${money(p.gia_ban_hien_tai)}</small>
                `;
            } else {
                itemEl.className = 'picker-item' + (isAlreadySelected ? ' selected-disabled' : '');
                itemEl.innerHTML = `
                    <div class="picker-item-left">
                        <span class="picker-item-name">${p.ten_san_pham} ${isAlreadySelected ? '✓' : ''}</span>
                        <span class="stock-badge badge-available">Còn ${available} ${unit}</span>
                    </div>
                    <small class="text-muted">${money(p.gia_ban_hien_tai)}</small>
                `;

                if (!isAlreadySelected) {
                    itemEl.onclick = () => {
                        let defaultQ = 1;
                        const currentSize = selectTplSize?.value;
                        if (role === 'GIAY_GOI' && bomSelected[role].length === 0 && currentSize && fixedRules.giayGoi[currentSize]) {
                            defaultQ = fixedRules.giayGoi[currentSize];
                        } else if (role === 'RUY_BANG' && bomSelected[role].length === 0 && currentSize && fixedRules.ruyBang[currentSize]) {
                            defaultQ = fixedRules.ruyBang[currentSize];
                        } else if (role === 'XOP' && bomSelected[role].length === 0 && currentSize && fixedRules.xop[currentSize]) {
                            defaultQ = fixedRules.xop[currentSize];
                        }

                        const initialQty = Math.max(1, Math.min(defaultQ, available));

                        bomSelected[role].push({
                            id: p.ma_san_pham,
                            name: p.ten_san_pham,
                            qty: initialQty,
                            available: available,
                            unit: unit
                        });

                        renderGroupSelected(role);

                        // Đóng picker sau khi chọn để hiển thị chip và cho phép chỉnh số lượng
                        pickerBox.classList.remove('open');
                        if (searchInput) searchInput.value = '';
                        updatePickerList(role);
                        if (role === 'HOA_CHINH') updatePickerList('HOA_TRANG_TRI');
                        if (role === 'HOA_TRANG_TRI') updatePickerList('HOA_CHINH');
                    };
                }
            }

            listEl.appendChild(itemEl);
        });
    }

    function setupPicker(role) {
        const pickerBox = document.getElementById(`picker-${role}`);
        if (!pickerBox) return;

        const trigger = pickerBox.querySelector('.picker-trigger');
        const dropdown = pickerBox.querySelector('.picker-dropdown');
        const searchInput = pickerBox.querySelector('.picker-search-input');

        // Mở/đóng dropdown khi click trigger
        trigger.onclick = (e) => {
            e.stopPropagation();
            const isOpen = pickerBox.classList.contains('open');

            // Đóng các picker khác
            document.querySelectorAll('.bom-picker.open').forEach(p => {
                if (p !== pickerBox) p.classList.remove('open');
            });

            if (!isOpen) {
                pickerBox.classList.add('open');
                if (searchInput) {
                    searchInput.value = '';
                    updatePickerList(role);
                    setTimeout(() => searchInput.focus(), 50);
                }
            } else {
                pickerBox.classList.remove('open');
            }
        };

        trigger.onkeydown = (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
            }
        };

        // Lọc khi gõ tìm kiếm
        if (searchInput) {
            searchInput.oninput = () => updatePickerList(role);

            // Nhấn Enter để chọn ngay item đầu tiên nếu có
            searchInput.onkeydown = (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstAvailable = dropdown.querySelector('.picker-item:not(.selected-disabled):not(.out-of-stock)');
                    if (firstAvailable) firstAvailable.click();
                } else if (e.key === 'Escape') {
                    pickerBox.classList.remove('open');
                }
            };
        }

        dropdown.onclick = (e) => e.stopPropagation();
    }

    // Khởi tạo 6 nhóm BOM
    ['HOA_CHINH', 'HOA_TRANG_TRI', 'GIAY_GOI', 'RUY_BANG', 'PHU_KIEN_TRANG_TRI', 'XOP'].forEach(r => {
        setupPicker(r);
        renderGroupSelected(r);
    });

    // Đóng dropdown picker khi click ra ngoài
    document.addEventListener('click', () => {
        document.querySelectorAll('.bom-picker.open').forEach(p => p.classList.remove('open'));
    });

    // =========================================================================
    // 23. KIỂM TRA ĐỊNH MỨC & KHẢ DỤNG KHO REALTIME (TÁCH BIỆT 2 LOẠI)
    // =========================================================================
    function recalcSummary() {
        const opt = selectTplSize.options[selectTplSize.selectedIndex];
        const s = opt?.value;

        const sumRole = r => (bomSelected[r] || []).reduce((acc, cur) => acc + cur.qty, 0);

        const hoaChinh = sumRole('HOA_CHINH');
        const hoaTT    = sumRole('HOA_TRANG_TRI');
        const tongHoa  = hoaChinh + hoaTT;
        const giay     = sumRole('GIAY_GOI');
        const ruy      = sumRole('RUY_BANG');
        const xop      = sumRole('XOP');
        const phukien  = sumRole('PHU_KIEN_TRANG_TRI');

        const stdBong = s ? (sizesData[s] || 0) : null;
        const stdGiay = s ? (fixedRules.giayGoi[s] || 0) : null;
        const stdRuy  = s ? (fixedRules.ruyBang[s] || 0) : null;
        const stdXop  = s ? (fixedRules.xop[s] || 0) : null;
        const maxPK   = s ? (fixedRules.phuKien[s] || 0) : null;

        // Subtitle note
        const valSizeNote = document.getElementById('val-size-note');
        if (valSizeNote) {
            if (!s) {
                valSizeNote.textContent = 'Chưa chọn size';
            } else {
                valSizeNote.textContent = `Size ${s} (${stdBong} bông)`;
            }
        }

        // 1. Hoa (Hoa chính + Hoa trang trí)
        const valHoa = document.getElementById('sum-val-hoa');
        const bdgHoa = document.getElementById('sum-badge-hoa');
        if (!s) {
            valHoa.textContent = `${tongHoa} / ?`;
            bdgHoa.className = 'summary-badge status-gray';
            bdgHoa.textContent = 'Chưa chọn size';
        } else {
            valHoa.textContent = `${tongHoa} / ${stdBong}`;
            if (hoaChinh < 1) {
                bdgHoa.className = 'summary-badge status-warn';
                bdgHoa.textContent = 'Thiếu hoa chính';
            } else if (tongHoa === stdBong) {
                bdgHoa.className = 'summary-badge status-ok';
                bdgHoa.textContent = 'Đạt';
            } else if (tongHoa < stdBong) {
                bdgHoa.className = 'summary-badge status-warn';
                bdgHoa.textContent = `Thiếu ${stdBong - tongHoa}`;
            } else {
                bdgHoa.className = 'summary-badge status-danger';
                bdgHoa.textContent = `Vượt ${tongHoa - stdBong}`;
            }
        }

        // 2. Giấy gói
        const valGiay = document.getElementById('sum-val-giay');
        const bdgGiay = document.getElementById('sum-badge-giay');
        if (!s) {
            valGiay.textContent = `${giay} / ?`;
            bdgGiay.className = 'summary-badge status-gray';
            bdgGiay.textContent = 'Chưa chọn size';
        } else {
            valGiay.textContent = `${giay} / ${stdGiay}`;
            if (giay === stdGiay) {
                bdgGiay.className = 'summary-badge status-ok';
                bdgGiay.textContent = 'Đạt';
            } else if (giay < stdGiay) {
                bdgGiay.className = 'summary-badge status-warn';
                bdgGiay.textContent = `Thiếu ${stdGiay - giay}`;
            } else {
                bdgGiay.className = 'summary-badge status-danger';
                bdgGiay.textContent = `Vượt ${giay - stdGiay}`;
            }
        }

        // 3. Ruy băng
        const valRuy = document.getElementById('sum-val-ruy');
        const bdgRuy = document.getElementById('sum-badge-ruy');
        if (!s) {
            valRuy.textContent = `${ruy} / ?`;
            bdgRuy.className = 'summary-badge status-gray';
            bdgRuy.textContent = 'Chưa chọn size';
        } else {
            valRuy.textContent = `${ruy} / ${stdRuy}`;
            if (ruy === stdRuy) {
                bdgRuy.className = 'summary-badge status-ok';
                bdgRuy.textContent = 'Đạt';
            } else if (ruy < stdRuy) {
                bdgRuy.className = 'summary-badge status-warn';
                bdgRuy.textContent = `Thiếu ${stdRuy - ruy}`;
            } else {
                bdgRuy.className = 'summary-badge status-danger';
                bdgRuy.textContent = `Vượt ${ruy - stdRuy}`;
            }
        }

        // 4. Mút xốp
        const valXop = document.getElementById('sum-val-xop');
        const bdgXop = document.getElementById('sum-badge-xop');
        if (!s) {
            valXop.textContent = `${xop} / ?`;
            bdgXop.className = 'summary-badge status-gray';
            bdgXop.textContent = 'Chưa chọn size';
        } else {
            valXop.textContent = `${xop} / ${stdXop}`;
            if (xop === stdXop) {
                bdgXop.className = 'summary-badge status-ok';
                bdgXop.textContent = 'Đạt';
            } else if (xop < stdXop) {
                bdgXop.className = 'summary-badge status-warn';
                bdgXop.textContent = `Thiếu ${stdXop - xop}`;
            } else {
                bdgXop.className = 'summary-badge status-danger';
                bdgXop.textContent = `Vượt ${xop - stdXop}`;
            }
        }

        // 5. Phụ kiện
        const valPK = document.getElementById('sum-val-phukien');
        const bdgPK = document.getElementById('sum-badge-phukien');
        if (!s) {
            valPK.textContent = `${phukien} / ?`;
            bdgPK.className = 'summary-badge status-gray';
            bdgPK.textContent = 'Chưa chọn size';
        } else {
            valPK.textContent = `${phukien} / ≤${maxPK}`;
            if (phukien <= maxPK) {
                bdgPK.className = 'summary-badge status-ok';
                bdgPK.textContent = 'Đạt';
            } else {
                bdgPK.className = 'summary-badge status-danger';
                bdgPK.textContent = `Vượt ${phukien - maxPK}`;
            }
        }

        // 6. KHẢ DỤNG KHO (TÁCH RIÊNG KHỎI ĐỊNH MỨC)
        const valStock = document.getElementById('sum-val-stock');
        const bdgStock = document.getElementById('sum-badge-stock');

        let allSelectedItems = [];
        Object.entries(bomSelected).forEach(([role, list]) => {
            list.forEach(item => allSelectedItems.push(item));
        });

        let hasOutOfStock = false;
        let hasExceededStock = false;
        let stockMissingCount = 0;

        allSelectedItems.forEach(item => {
            if (item.available <= 0) {
                hasOutOfStock = true;
            }
            if (item.qty > item.available) {
                hasExceededStock = true;
                stockMissingCount += (item.qty - item.available);
            }
        });

        if (valStock && bdgStock) {
            if (allSelectedItems.length === 0) {
                valStock.textContent = '0 món';
                bdgStock.className = 'summary-badge status-gray';
                bdgStock.textContent = 'Chưa chọn';
            } else if (hasOutOfStock) {
                valStock.textContent = 'Có mặt hàng hết';
                bdgStock.className = 'summary-badge status-danger';
                bdgStock.textContent = 'Hết hàng';
            } else if (hasExceededStock) {
                valStock.textContent = `Thiếu ${stockMissingCount}`;
                bdgStock.className = 'summary-badge status-danger';
                bdgStock.textContent = 'Thiếu kho';
            } else {
                valStock.textContent = 'Đủ tồn kho';
                bdgStock.className = 'summary-badge status-ok';
                bdgStock.textContent = 'Đạt';
            }
        }

        // ── CẬP NHẬT TRẠNG THÁI & HƯỚNG DẪN NÚT LƯU MẪU ──
        const isNameDuplicate = inputTplName ? existingTemplateNames.some(name => normalizeName(name) === normalizeName(inputTplName.value)) : false;
        const isNameEmpty = !inputTplName || !inputTplName.value.trim();
        const isSizeEmpty = !s;
        const isKieuEmpty = !selectTplKieu || !selectTplKieu.value;

        const isBomQuotaValid = s && (hoaChinh >= 1) && (tongHoa === stdBong) && (giay === stdGiay) && (ruy === stdRuy) && (xop === stdXop) && (phukien <= maxPK);
        const isStockValid = (allSelectedItems.length > 0) && !hasOutOfStock && !hasExceededStock;

        const hoaChinhIds = (bomSelected['HOA_CHINH'] || []).map(x => x.id);
        const hasDuplicateFlower = (bomSelected['HOA_TRANG_TRI'] || []).some(x => hoaChinhIds.includes(x.id));

        if (btnSubmitTpl && !btnSubmitTpl.dataset.submitting) {
            btnSubmitTpl.disabled = false;
            if (isNameDuplicate) {
                btnSubmitTpl.title = 'Tên mẫu đã tồn tại. Vui lòng chọn tên khác.';
            } else if (isNameEmpty) {
                btnSubmitTpl.title = 'Vui lòng nhập tên mẫu bó hoa.';
            } else if (isKieuEmpty) {
                btnSubmitTpl.title = 'Vui lòng chọn kiểu dáng bó hoa.';
            } else if (isSizeEmpty) {
                btnSubmitTpl.title = 'Vui lòng chọn kích cỡ (size) bó hoa.';
            } else if (!isStockValid) {
                btnSubmitTpl.title = 'Thành phần BOM không đủ tồn kho khả dụng.';
            } else if (!isBomQuotaValid) {
                btnSubmitTpl.title = 'Chưa đạt định mức BOM theo size.';
            } else if (hasDuplicateFlower) {
                btnSubmitTpl.title = 'Hoa trang trí không được trùng với hoa chính.';
            } else {
                btnSubmitTpl.title = 'Lưu mẫu bó hoa';
            }
        }
    }

    // =========================================================================
    // FORM ACTIONS: LƯU MẪU & LÀM MỚI
    // =========================================================================
    btnResetTpl.onclick = () => {
        formCreateTpl.reset();
        if (msgCreateTpl) msgCreateTpl.classList.add('d-none');
        imgTplPreview.classList.add('d-none');
        imgTplHolder.classList.remove('d-none');
        inputTplName?.classList.remove('input-invalid-border');
        if (tplNameFeedback) tplNameFeedback.classList.add('d-none');
        Object.keys(bomSelected).forEach(k => {
            bomSelected[k] = [];
            renderGroupSelected(k);
            updatePickerList(k);
        });
        document.getElementById('note-hoa-chinh').textContent    = 'Có thể chọn nhiều loại (≥ 1 bông)';
        document.getElementById('note-hoa-trang-tri').textContent= 'Hoa phụ / hoa đệm theo size';
        document.getElementById('note-giay-goi').textContent     = 'Chuẩn: -- tờ';
        document.getElementById('note-ruy-bang').textContent    = 'Chuẩn: -- mét';
        document.getElementById('note-phu-kien').textContent    = 'Tối đa: -- món';
        document.getElementById('note-mut-xop').textContent     = 'Chuẩn: -- miếng';
        btnSubmitTpl.disabled = false;
        delete btnSubmitTpl.dataset.submitting;
        btnSubmitTpl.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Lưu mẫu';
        recalcSummary();
    };

    async function submitCreateTemplate(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (btnSubmitTpl.dataset.submitting) return;
        if (msgCreateTpl) msgCreateTpl.classList.add('d-none');

        // 1. Validate thông tin cơ bản
        const inputName     = document.getElementById('input-tpl-name');
        const selectConcept = document.getElementById('select-tpl-concept');
        const name    = (inputName?.value || '').trim();
        const kieu    = selectTplKieu?.value;
        const size    = selectTplSize?.value;
        const concept = selectConcept?.value;

        if (!name) {
            showTplMessage('Vui lòng nhập tên mẫu bó hoa.');
            inputName?.focus();
            return;
        }

        // Validate trùng tên không phân biệt hoa/thường & trim
        const isDuplicate = existingTemplateNames.some(n => normalizeName(n) === normalizeName(name));
        if (isDuplicate) {
            showTplMessage('Tên mẫu bó hoa đã tồn tại. Vui lòng chọn tên khác.');
            inputName?.focus();
            return;
        }

        if (!kieu) {
            showTplMessage('Vui lòng chọn kiểu dáng bó hoa.');
            selectTplKieu?.focus();
            return;
        }
        if (!size) {
            showTplMessage('Vui lòng chọn kích cỡ (size) bó hoa.');
            selectTplSize?.focus();
            return;
        }

        // 2. Validate định mức nguyên vật liệu (BOM)
        let allItems = [];
        Object.entries(bomSelected).forEach(([role, list]) => {
            list.forEach(item => {
                allItems.push({ ma_san_pham: item.id, name: item.name, vai_tro: role, so_luong: item.qty, available: item.available, unit: item.unit });
            });
        });

        if (allItems.length === 0) {
            showTplMessage('Vui lòng chọn các thành phần nguyên vật liệu (hoa chính, giấy gói, ruy băng, xốp) cho mẫu bó hoa.');
            return;
        }

        // Kiểm tra tồn kho khả dụng trước khi gửi
        for (const it of allItems) {
            if (it.available <= 0) {
                showTplMessage(`"${it.name}" đã hết hàng khả dụng trong kho.`);
                return;
            }
            if (it.so_luong > it.available) {
                showTplMessage(`"${it.name}" không còn đủ tồn khả dụng. Cần ${it.so_luong}, hiện còn ${it.available}.`);
                return;
            }
        }

        // Kiểm tra hoa trang trí trùng hoa chính
        const hoaChinhIds = (bomSelected['HOA_CHINH'] || []).map(x => x.id);
        const hasDuplicateFlower = (bomSelected['HOA_TRANG_TRI'] || []).some(x => hoaChinhIds.includes(x.id));
        if (hasDuplicateFlower) {
            showTplMessage('Hoa trang trí không được trùng với hoa chính.');
            return;
        }

        const sumRole = r => (bomSelected[r] || []).reduce((acc, cur) => acc + cur.qty, 0);
        const hoaChinh = sumRole('HOA_CHINH');
        const hoaTT    = sumRole('HOA_TRANG_TRI');
        const tongHoa  = hoaChinh + hoaTT;
        const giay     = sumRole('GIAY_GOI');
        const ruy      = sumRole('RUY_BANG');
        const xop      = sumRole('XOP');
        const phukien  = sumRole('PHU_KIEN_TRANG_TRI');

        const stdBong = sizesData[size] || 0;
        const stdGiay = fixedRules.giayGoi[size] || 0;
        const stdRuy  = fixedRules.ruyBang[size] || 0;
        const stdXop  = fixedRules.xop[size] || 0;
        const maxPK   = fixedRules.phuKien[size] || 0;

        if (hoaChinh < 1) {
            showTplMessage('Vui lòng chọn ít nhất 1 loại hoa chính (≥ 1 bông).');
            return;
        }
        if (tongHoa !== stdBong) {
            const diff = Math.abs(stdBong - tongHoa);
            const statusText = tongHoa < stdBong ? `còn thiếu ${diff} bông` : `đang vượt ${diff} bông`;
            showTplMessage(`Size ${size} cần đúng ${stdBong} bông (hoa chính + hoa trang trí). Hiện tại bạn đã chọn ${tongHoa} bông (${statusText}).`);
            return;
        }
        if (giay !== stdGiay) {
            const diff = Math.abs(stdGiay - giay);
            const statusText = giay < stdGiay ? `còn thiếu ${diff} tờ` : `đang vượt ${diff} tờ`;
            showTplMessage(`Size ${size} cần đúng ${stdGiay} tờ giấy gói. Hiện tại bạn đã chọn ${giay} tờ (${statusText}).`);
            return;
        }
        if (ruy !== stdRuy) {
            const diff = Math.abs(stdRuy - ruy);
            const statusText = ruy < stdRuy ? `còn thiếu ${diff} mét` : `đang vượt ${diff} mét`;
            showTplMessage(`Size ${size} cần đúng ${stdRuy} mét ruy băng. Hiện tại bạn đã chọn ${ruy} mét (${statusText}).`);
            return;
        }
        if (xop !== stdXop) {
            const diff = Math.abs(stdXop - xop);
            const statusText = xop < stdXop ? `còn thiếu ${diff} miếng` : `đang vượt ${diff} miếng`;
            showTplMessage(`Size ${size} cần đúng ${stdXop} miếng mút xốp. Hiện tại bạn đã chọn ${xop} miếng (${statusText}).`);
            return;
        }
        if (phukien > maxPK) {
            showTplMessage(`Size ${size} chỉ được tối đa ${maxPK} món phụ kiện trang trí. Hiện tại bạn đã chọn ${phukien} món (vượt ${phukien - maxPK} món).`);
            return;
        }

        btnSubmitTpl.disabled = true;
        btnSubmitTpl.dataset.submitting = 'true';
        btnSubmitTpl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu mẫu...';

        try {
            const fd = new FormData(formCreateTpl);
            const imgInput = document.getElementById('input-tpl-image');
            if (!imgInput?.files?.length) {
                fd.delete('hinh_anh');
            }
            if (!concept) {
                fd.delete('concept');
            }
            allItems.forEach((it, i) => {
                fd.append(`thanh_phan[${i}][ma_san_pham]`, it.ma_san_pham);
                fd.append(`thanh_phan[${i}][vai_tro]`, it.vai_tro);
                fd.append(`thanh_phan[${i}][so_luong]`, it.so_luong);
            });

            const r = await api(formCreateTpl.action, 'POST', fd);
            showTplMessage(r.message || 'Đã tạo mẫu bó hoa thành công!', true);
            if (r.data?.ten_mau) {
                existingTemplateNames.push(r.data.ten_mau);
            }
            setTimeout(() => {
                window.location.hash = 'tab-danh-sach-mau';
                location.reload();
            }, 800);
        } catch (err) {
            let m = err.message;
            if (err.errors) {
                const list = Object.values(err.errors).flat();
                if (list.length) m = list.join(' · ');
            }
            showTplMessage(m);
            btnSubmitTpl.disabled = false;
            delete btnSubmitTpl.dataset.submitting;
            btnSubmitTpl.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Lưu mẫu';
        }
    }

    formCreateTpl.onsubmit = submitCreateTemplate;
    btnSubmitTpl.onclick   = submitCreateTemplate;

    // =========================================================================
    // MODAL CHI TIẾT GIÁ BÓ HOA & BÁO HỎNG (TAB BÓ HOA SẴN)
    // =========================================================================
    document.querySelectorAll('[data-bo-chitiet-gia]').forEach(button => {
        button.onclick = async () => {
            const url = button.dataset.boChitietGia;
            try {
                const r = await api(url);
                document.getElementById('ctg_ten').textContent  = r.data.ten_bo_hoa;
                document.getElementById('ctg_ma').textContent   = r.data.ma_bo_hoa;
                document.getElementById('ctg_size').textContent = r.data.size;
                document.getElementById('ctg_kieu').textContent = r.data.kieu_bo_hoa_text;

                document.getElementById('ctg_size_label').textContent = r.data.size;
                document.getElementById('ctg_kieu_label').textContent = r.data.kieu_bo_hoa_text;

                const tbody = document.getElementById('ctg_nguyen_lieu');
                tbody.innerHTML = '';
                r.data.chi_tiet.forEach(ct => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${ct.ten_san_pham}</td>
                        <td><span class="badge bg-secondary">${ct.vai_tro_text}</span></td>
                        <td class="text-end">${ct.so_luong} ${ct.don_vi_tinh || ''}</td>
                        <td class="text-end">${money(ct.gia_ban)}</td>
                        <td class="text-end fw-semibold">${money(ct.thanh_tien)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('ctg_tong_nl').textContent  = money(r.data.tien_nguyen_lieu);
                document.getElementById('ctg_pp_size').textContent  = money(r.data.phu_phi.size);
                document.getElementById('ctg_pp_kieu').textContent  = money(r.data.phu_phi.kieu_dang);
                document.getElementById('ctg_pp_tong').textContent  = money(r.data.phu_phi.tong);
                document.getElementById('ctg_gia_ban').textContent  = money(r.data.gia_ban);

                new bootstrap.Modal(document.getElementById('modalChiTietGiaBo')).show();
            } catch (e) {
                tell(e.message);
            }
        };
    });

    document.querySelectorAll('[data-bo-baohong]').forEach(button => {
        button.onclick = () => {
            const url = button.dataset.boBaohong;
            const ten = button.dataset.ten;
            const sl  = parseInt(button.dataset.sl) || 0;
            if (sl < 1) { tell('Bó hoa không còn số lượng để báo hỏng.'); return; }

            document.getElementById('bo_hh_url').value        = url;
            document.getElementById('bo_hh_ten').textContent  = ten;
            document.getElementById('bo_hh_sl').textContent   = sl;
            document.getElementById('bo_hh_so_luong').value   = '';
            document.getElementById('bo_hh_so_luong').max     = sl;
            document.getElementById('bo_hh_ly_do').value      = '';
            document.getElementById('bo_hh_ghi_chu').value    = '';
            document.getElementById('bo_hh_sl_error').style.display = 'none';
            document.getElementById('bo_hh_submit').disabled  = true;

            new bootstrap.Modal(document.getElementById('modalHangHongBo')).show();
        };
    });

    (() => {
        const modal = document.getElementById('modalHangHongBo');
        if (!modal) return;
        const sl    = document.getElementById('bo_hh_so_luong');
        const ly    = document.getElementById('bo_hh_ly_do');
        const btn   = document.getElementById('bo_hh_submit');
        const slErr = document.getElementById('bo_hh_sl_error');

        function validate() {
            const n   = parseInt(sl.value) || 0;
            const max = parseInt(sl.max) || 0;
            const l   = ly.value.trim();
            let ok = true;
            if (n < 1 || n > max) { slErr.style.display = 'block'; ok = false; }
            else { slErr.style.display = 'none'; }
            if (!l) ok = false;
            btn.disabled = !ok;
        }

        sl.addEventListener('input', validate);
        ly.addEventListener('input', validate);

        document.getElementById('formHangHongBo').onsubmit = async (e) => {
            e.preventDefault();
            btn.disabled = true;
            try {
                const fd = new FormData();
                fd.set('so_luong_hong', sl.value);
                fd.set('ly_do', ly.value);
                fd.set('ghi_chu', document.getElementById('bo_hh_ghi_chu').value);

                await api(document.getElementById('bo_hh_url').value, 'POST', fd);
                bootstrap.Modal.getInstance(modal).hide();
                location.reload();
            } catch (err) {
                let m = err.message;
                if (err.errors) {
                    const list = Object.values(err.errors).flat();
                    if (list.length) m = list.join(' · ');
                }
                tell(m);
                btn.disabled = false;
            }
        };
    })();

    // =========================================================================
    // TAB BÓ HOA SẴN: COLLAPSIBLE PANEL, PREVIEW NGUYÊN LIỆU & TẠO BÓ AJAX
    // =========================================================================
    const premadePanel      = document.getElementById('premade-create-panel');
    const btnTogglePremade  = document.getElementById('btn-toggle-create-premade');
    const btnClosePremade   = document.getElementById('btn-close-premade-panel');
    const btnCancelPremade  = document.getElementById('btn-cancel-ready-create');
    const btnEmptyPremade   = document.getElementById('btn-empty-create-premade');
    const selectReadyMau    = document.getElementById('select-ready-mau');
    const inputReadyQty     = document.getElementById('input-ready-qty');
    const btnCheckMaterial  = document.getElementById('btn-check-material');
    const previewTbody      = document.getElementById('preview-materials-tbody');
    const previewSummaryBdg = document.getElementById('preview-summary-badge');
    const previewAlertNote  = document.getElementById('preview-alert-note');
    const btnSubmitReady    = document.getElementById('btn-submit-ready-create');
    const formReadyBouquet  = document.getElementById('form-create-ready-bouquet');
    const msgReadyBouquet   = document.getElementById('ready-bouquet-message');

    function openPremadePanel() {
        if (!premadePanel) return;
        premadePanel.classList.remove('d-none');
        premadePanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (selectReadyMau) selectReadyMau.focus();
    }

    function closePremadePanel() {
        if (!premadePanel) return;
        premadePanel.classList.add('d-none');
        if (formReadyBouquet) formReadyBouquet.reset();
        if (msgReadyBouquet) msgReadyBouquet.classList.add('d-none');
        resetMaterialPreview();
    }

    btnTogglePremade?.addEventListener('click', () => {
        if (premadePanel && premadePanel.classList.contains('d-none')) {
            openPremadePanel();
        } else {
            closePremadePanel();
        }
    });

    btnClosePremade?.addEventListener('click', closePremadePanel);
    btnCancelPremade?.addEventListener('click', closePremadePanel);
    btnEmptyPremade?.addEventListener('click', openPremadePanel);

    function resetMaterialPreview() {
        if (previewSummaryBdg) {
            previewSummaryBdg.className = 'summary-badge status-gray';
            previewSummaryBdg.textContent = 'Chưa chọn mẫu';
        }
        if (previewTbody) {
            previewTbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-muted small">
                        Vui lòng chọn mẫu bó hoa và số lượng để xem trước định mức nguyên liệu.
                    </td>
                </tr>
            `;
        }
        if (previewAlertNote) {
            previewAlertNote.className = 'alert py-2 px-3 mt-2 mb-0 small d-none';
            previewAlertNote.innerHTML = '';
        }
        if (btnSubmitReady) {
            btnSubmitReady.disabled = true;
        }
    }

    function updateMaterialPreview() {
        const templateId = selectReadyMau?.value;
        const qty = Math.max(1, parseInt(inputReadyQty?.value) || 1);
        if (inputReadyQty) inputReadyQty.value = qty;

        if (!templateId) {
            resetMaterialPreview();
            return;
        }

        const template = templatesBomData ? templatesBomData[templateId] : null;
        if (!template) {
            resetMaterialPreview();
            return;
        }

        const bomItems = template.thanh_phan || [];
        if (bomItems.length === 0) {
            if (previewSummaryBdg) {
                previewSummaryBdg.className = 'summary-badge status-danger';
                previewSummaryBdg.textContent = 'Chưa có BOM';
            }
            if (previewTbody) {
                previewTbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger small">
                            Mẫu <strong>${template.ten}</strong> chưa được cấu hình định mức nguyên liệu (BOM). Vui lòng cập nhật mẫu trước.
                        </td>
                    </tr>
                `;
            }
            if (previewAlertNote) {
                previewAlertNote.className = 'alert alert-danger py-2 px-3 mt-2 mb-0 small';
                previewAlertNote.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Mẫu này chưa có công thức nguyên liệu, không thể tạo bó thành phẩm.';
                previewAlertNote.classList.remove('d-none');
            }
            if (btnSubmitReady) btnSubmitReady.disabled = true;
            return;
        }

        let allSufficient = true;
        const missingDetails = [];
        const rowsHtml = [];

        bomItems.forEach(item => {
            const needPerUnit = Number(item.so_luong) || 0;
            const totalNeed = needPerUnit * qty;
            const available = Number(materialStockMap[item.ma_san_pham] || 0);
            const isMissing = totalNeed > available;

            if (isMissing) {
                allSufficient = false;
                missingDetails.push(`Không đủ <strong>${item.ten_san_pham}</strong>: cần ${totalNeed} ${item.don_vi_tinh}, khả dụng ${available} ${item.don_vi_tinh}`);
            }

            rowsHtml.push(`
                <tr class="${isMissing ? 'row-missing' : ''}">
                    <td>
                        <strong class="${isMissing ? 'text-danger' : 'text-dark'}">${item.ten_san_pham}</strong>
                        <span class="text-muted small ms-1">(${item.don_vi_tinh})</span>
                    </td>
                    <td class="text-center font-monospace">${needPerUnit}</td>
                    <td class="text-center font-monospace fw-bold">${totalNeed}</td>
                    <td class="text-center font-monospace ${available >= totalNeed ? 'text-success fw-semibold' : 'text-danger fw-bold'}">
                        ${available}
                    </td>
                    <td class="text-end">
                        ${isMissing
                            ? `<span class="summary-badge status-danger">Thiếu ${totalNeed - available}</span>`
                            : `<span class="summary-badge status-ok">Đủ</span>`
                        }
                    </td>
                </tr>
            `);
        });

        if (previewTbody) previewTbody.innerHTML = rowsHtml.join('');

        if (!allSufficient) {
            if (previewSummaryBdg) {
                previewSummaryBdg.className = 'summary-badge status-danger';
                previewSummaryBdg.textContent = 'Thiếu nguyên liệu';
            }
            if (previewAlertNote) {
                previewAlertNote.className = 'alert alert-danger py-2 px-3 mt-2 mb-0 small';
                previewAlertNote.innerHTML = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold">Kho không đủ nguyên liệu để tạo ${qty} bó "${template.ten}":</div>
                            <ul class="mb-0 ps-3 mt-1">
                                ${missingDetails.map(d => `<li>${d}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                `;
                previewAlertNote.classList.remove('d-none');
            }
            if (btnSubmitReady) btnSubmitReady.disabled = true;
        } else {
            if (previewSummaryBdg) {
                previewSummaryBdg.className = 'summary-badge status-ok';
                previewSummaryBdg.textContent = 'Đủ nguyên liệu';
            }
            if (previewAlertNote) {
                previewAlertNote.className = 'alert alert-success py-2 px-3 mt-2 mb-0 small';
                previewAlertNote.innerHTML = `
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <strong>Đủ nguyên liệu:</strong> Kho đáp ứng đủ toàn bộ định mức để tạo <strong>${qty}</strong> bó <strong>"${template.ten}"</strong>.
                `;
                previewAlertNote.classList.remove('d-none');
            }
            if (btnSubmitReady) btnSubmitReady.disabled = false;
        }
    }

    selectReadyMau?.addEventListener('change', updateMaterialPreview);
    inputReadyQty?.addEventListener('input', updateMaterialPreview);
    btnCheckMaterial?.addEventListener('click', updateMaterialPreview);

    if (formReadyBouquet) {
        formReadyBouquet.onsubmit = async (e) => {
            e.preventDefault();
            if (btnSubmitReady && btnSubmitReady.disabled) return;

            const submitBtn = btnSubmitReady || formReadyBouquet.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : 'Tạo bó';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tạo...';
            }
            if (msgReadyBouquet) msgReadyBouquet.classList.add('d-none');

            try {
                const fd = new FormData(formReadyBouquet);
                const res = await api(formReadyBouquet.action, 'POST', fd);

                tell(res.message || 'Tạo bó hoa sẵn vào kho thành công!', true);
                closePremadePanel();
                setTimeout(() => {
                    window.location.hash = 'tab-bo-hoa-san';
                    location.reload();
                }, 800);
            } catch (err) {
                let m = err.message;
                if (err.errors) {
                    const list = Object.values(err.errors).flat();
                    if (list.length) m = list.join(' · ');
                }
                if (msgReadyBouquet) {
                    msgReadyBouquet.className = 'alert alert-danger py-2 px-3 mb-3 rounded-2';
                    msgReadyBouquet.textContent = m;
                    msgReadyBouquet.classList.remove('d-none');
                    msgReadyBouquet.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    tell(m);
                }
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        };
    }

})();
</script>
@endpush