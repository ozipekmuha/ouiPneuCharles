<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
function normalizeTireDimension($width, $ratio, $diameter)
{
    $w = trim($width);
    $r = trim($ratio);
    $d = trim($diameter);
    if ($w && $r && $d) {
        return $w . '/' . $r . 'R' . $d;
    }
    return '';
}

$pneus = [];
try {
    $stmt_pneus = $pdo->query("SELECT id, nom, image, taille, saison, prix, stock_disponible, est_actif, description, specifications FROM Pneus ORDER BY nom ASC");
    $pneus = $stmt_pneus->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération pneus: " . $e->getMessage());
}

$total_produits = count($pneus);

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Pneus - Ouipneu.fr</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
    :root {
        --pro-primary: #FFD700;
        --pro-secondary: #FFA500;
        --pro-accent: #FF6B35;
        --pro-success: #4CAF50;
        --pro-warning: #FF9800;
        --pro-error: #F44336;
        --pro-info: #2196F3;
        --pro-dark: #0a0a0a;
        --pro-surface: rgba(255, 255, 255, 0.05);
        --pro-surface-hover: rgba(255, 255, 255, 0.08);
        --pro-border: rgba(255, 215, 0, 0.2);
        --pro-border-hover: rgba(255, 215, 0, 0.4);
        --pro-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        --pro-shadow-hover: 0 16px 48px rgba(0, 0, 0, 0.4);
        --pro-blur: blur(20px);
        --pro-transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    
    
    .pro-search-section {
        margin-bottom: 3rem;
    }
    
    .search-hero {
        background: linear-gradient(135deg, var(--pro-surface), var(--pro-surface-hover));
        border: 1px solid var(--pro-border);
        border-radius: 20px;
        padding: 2rem;
        backdrop-filter: var(--pro-blur);
        box-shadow: var(--pro-shadow);
    }
    
    .search-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: center;
    }
    
    .search-input-wrapper {
        position: relative;
        flex: 1;
        max-width: 600px;
    }
    
    .search-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--pro-primary);
        font-size: 1.2rem;
        z-index: 2;
        transition: var(--pro-transition);
    }
    
    .search-input-wrapper input {
        width: 100%;
        padding: 1.25rem 1.25rem 1.25rem 3.5rem;
        background: var(--pro-surface);
        border: 2px solid var(--pro-border);
        border-radius: 16px;
        color: var(--text-light);
        font-size: 1.1rem;
        font-weight: 500;
        transition: var(--pro-transition);
        backdrop-filter: var(--pro-blur);
    }
    
    .search-input-wrapper input:focus {
        outline: none;
        border-color: var(--pro-primary);
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.15);
        background: var(--pro-surface-hover);
        transform: translateY(-2px);
    }
    
    .search-input-wrapper input:focus + .search-icon {
        color: var(--pro-secondary);
        transform: translateY(-50%) scale(1.1);
    }
    
    .search-input-wrapper input::placeholder {
        color: var(--text-secondary);
        font-weight: 400;
    }
    
    .search-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1.25rem 2rem;
        background: linear-gradient(135deg, var(--pro-primary), var(--pro-secondary));
        border: none;
        border-radius: 16px;
        color: #000;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--pro-transition);
        box-shadow: 0 4px 16px rgba(255, 215, 0, 0.3);
    }
    
    .search-btn:hover {
        background: linear-gradient(135deg, var(--pro-secondary), var(--pro-primary));
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(255, 215, 0, 0.4);
    }
    
    .search-btn:active {
        transform: translateY(-1px);
    }
    
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 12px;
        margin-top: 0.5rem;
        backdrop-filter: var(--pro-blur);
        box-shadow: var(--pro-shadow);
        z-index: 1000;
        display: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .search-suggestions.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .suggestion-item {
        padding: 1rem 1.25rem;
        cursor: pointer;
        transition: var(--pro-transition);
        border-bottom: 1px solid var(--pro-border);
    }
    
    .suggestion-item:hover {
        background: var(--pro-surface-hover);
        color: var(--pro-primary);
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    .quick-filters {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .quick-filter-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .quick-filter-tags {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .quick-tag {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 12px;
        color: var(--text-light);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: var(--pro-transition);
    }
    
    .quick-tag:hover {
        background: var(--pro-surface-hover);
        border-color: var(--pro-primary);
        color: var(--pro-primary);
        transform: translateY(-2px);
    }
    
    .quick-tag.active {
        background: linear-gradient(135deg, var(--pro-primary), var(--pro-secondary));
        border-color: var(--pro-primary);
        color: #000;
        box-shadow: 0 4px 16px rgba(255, 215, 0, 0.3);
    }
    
    .quick-tag i {
        font-size: 0.8rem;
    }
    
    
    .pro-controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 16px;
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        backdrop-filter: var(--pro-blur);
        box-shadow: var(--pro-shadow);
    }
    
    .controls-left {
        display: flex;
        align-items: center;
        gap: 2rem;
    }
    
    .controls-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .results-summary {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .results-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        color: var(--text-light);
    }
    
    .results-count i {
        color: var(--pro-primary);
        font-size: 1.1rem;
    }
    
    .results-count span:first-of-type {
        font-weight: 700;
        color: var(--pro-primary);
        font-size: 1.2rem;
    }
    
    .results-text {
        color: var(--text-secondary);
    }
    
    .active-filters-badge {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, var(--pro-warning), var(--pro-accent));
        border-radius: 12px;
        color: #000;
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(255, 152, 0, 0.3);
    }
    
    .active-filters-badge i {
        font-size: 0.8rem;
    }
    
    
    .sort-section {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .sort-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-light);
        font-weight: 500;
    }
    
    .sort-label i {
        color: var(--pro-primary);
        font-size: 0.8rem;
    }
    
    .sort-select {
        padding: 0.75rem 1rem;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 10px;
        color: var(--text-light);
        font-size: 0.9rem;
        cursor: pointer;
        transition: var(--pro-transition);
        min-width: 160px;
    }
    
    .sort-select:focus {
        outline: none;
        border-color: var(--pro-primary);
        box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.1);
    }
    
    .sort-select option {
        background: var(--pro-dark);
        color: var(--text-light);
    }
    
    .view-controls {
        display: flex;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .view-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: transparent;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        transition: var(--pro-transition);
        position: relative;
    }
    
    .view-btn:not(:last-child) {
        border-right: 1px solid var(--pro-border);
    }
    
    .view-btn:hover {
        color: var(--text-light);
        background: var(--pro-surface-hover);
    }
    
    .view-btn.active {
        color: var(--pro-primary);
        background: var(--pro-surface-hover);
    }
    
    .view-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--pro-primary);
    }
    
    .favorites-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        background: var(--pro-surface);
        border: 2px solid var(--pro-border);
        border-radius: 12px;
        color: var(--text-light);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--pro-transition);
        position: relative;
    }
    
    .favorites-btn:hover {
        background: var(--pro-surface-hover);
        border-color: var(--pro-error);
        color: var(--pro-error);
        transform: translateY(-2px);
    }
    
    .favorites-btn.active {
        background: linear-gradient(135deg, var(--pro-error), #ff6b6b);
        border-color: var(--pro-error);
        color: white;
        box-shadow: 0 4px 16px rgba(244, 67, 54, 0.3);
    }
    
    .favorites-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: var(--pro-error);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        min-width: 20px;
    }
    
    .modern-filters-container {
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        backdrop-filter: var(--pro-blur);
        box-shadow: var(--pro-shadow);
    }
    
    .modern-filters-form {
        width: 100%;
    }
    
    .filters-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto;
        gap: 1.5rem;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--accent-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    
    .filter-select {
        padding: 0.8rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 8px;
        color: var(--text-light);
        font-size: 0.95rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.1);
    }
    
    .filter-select option {
        background: var(--bg-dark);
        color: var(--text-light);
    }
    
    .dimensions-inputs {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .dimensions-inputs .filter-select {
        flex: 1;
        min-width: 0;
    }
    
    .dimension-separator {
        color: var(--accent-primary);
        font-weight: 600;
        font-size: 1.1rem;
        user-select: none;
    }
    
    .checkbox-filters {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .checkbox-filter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: background-color 0.3s ease;
    }
    
    .checkbox-filter:hover {
        background: rgba(255, 215, 0, 0.05);
    }
    
    .checkbox-filter input[type="checkbox"] {
        display: none;
    }
    
    .checkmark {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(255, 215, 0, 0.3);
        border-radius: 4px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .checkbox-filter input[type="checkbox"]:checked + .checkmark {
        background: var(--accent-primary);
        border-color: var(--accent-primary);
    }
    
    .checkbox-filter input[type="checkbox"]:checked + .checkmark::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #000;
        font-size: 0.8rem;
        font-weight: bold;
    }
    
    .checkbox-label {
        font-size: 0.9rem;
        color: var(--text-light);
        user-select: none;
    }
    
    .filter-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }
    
    .filter-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.8rem 1.2rem;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .filter-btn.primary {
        background: linear-gradient(135deg, var(--accent-primary), #FFA500);
        color: #000;
    }
    
    .filter-btn.primary:hover {
        background: linear-gradient(135deg, #FFA500, var(--accent-primary));
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
    }
    
    .filter-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        border: 1px solid rgba(255, 215, 0, 0.3);
    }
    
    .filter-btn.secondary:hover {
        background: rgba(255, 215, 0, 0.1);
        border-color: var(--accent-primary);
        color: var(--accent-primary);
    }
    
    @media (max-width: 1200px) {
        .filters-row {
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .filter-actions {
            grid-column: 1 / -1;
            justify-content: center;
            margin-top: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .filters-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .dimensions-inputs {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .dimension-separator {
            display: none;
        }
        
        .checkbox-filters {
            justify-content: center;
        }
        
        .filter-actions {
            flex-direction: column;
            width: 100%;
        }
        
        .filter-btn {
            width: 100%;
            justify-content: center;
        }
    }
    
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
        position: relative;
    }
    
    .pro-product-card {
        background: #2a2a2a;
        border: 1px solid #404040;
        border-radius: 8px;
        overflow: hidden;
        transition: border-color 0.2s ease;
        position: relative;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .pro-product-card:hover {
        border-color: #ffd700;
    }
    
    
    .product-image-container {
        position: relative;
        height: 180px;
        overflow: hidden;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    
    .product-badges {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        z-index: 5;
    }
    
    .product-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .product-badge.runflat-badge {
        background: #2196F3;
        color: white;
    }
    
    .product-badge.reinforced-badge {
        background: #ffd700;
        color: #000;
    }
    
    .product-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .pro-product-card:hover .product-overlay {
        opacity: 1;
    }
    
    .overlay-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .overlay-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 0.2s ease;
    }
    
    .overlay-btn.primary {
        background: #ffd700;
        color: #000;
    }
    
    .overlay-btn.primary:hover {
        background: #ffed4e;
    }
    
    .product-info {
        padding: 1.5rem;
    }
    
    .product-header {
        margin-bottom: 1rem;
    }
    
    .product-brand {
        font-size: 0.8rem;
        color: #ffd700;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .product-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #ffffff;
        margin: 0 0 0.75rem 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    
    .product-specifications {
        margin-bottom: 1.5rem;
    }
    
    .spec-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: #cccccc;
        padding: 0.25rem 0;
    }
    
    .spec-item:last-child {
        margin-bottom: 0;
    }
    
    .spec-item i {
        width: 14px;
        color: #ffd700;
        font-size: 0.7rem;
        text-align: center;
    }
    
    .spec-item span {
        flex: 1;
        font-weight: 500;
    }
    
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
    }
    
    .product-price-container {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .product-price {
        font-size: 1.4rem;
        font-weight: 700;
        color: #ffd700;
        line-height: 1;
    }
    
    .product-stock {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .product-stock.in-stock {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(76, 175, 80, 0.1));
        color: var(--pro-success);
        border: 1px solid rgba(76, 175, 80, 0.3);
    }
    
    .product-stock.low-stock {
        background: linear-gradient(135deg, rgba(255, 152, 0, 0.2), rgba(255, 152, 0, 0.1));
        color: var(--pro-warning);
        border: 1px solid rgba(255, 152, 0, 0.3);
    }
    
    .product-stock.out-of-stock {
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.2), rgba(244, 67, 54, 0.1));
        color: var(--pro-error);
        border: 1px solid rgba(244, 67, 54, 0.3);
    }
    
    .product-stock i {
        font-size: 0.6rem;
    }
    
    .product-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .product-cta-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: #ffd700;
        color: #000 !important;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: background-color 0.2s ease;
        white-space: nowrap;
        border: none;
        min-width: 140px;
    }
    
    .product-cta-btn span,
    .product-cta-btn i {
        color: #000 !important;
        display: inline-block;
    }
    
    .product-cta-btn:hover {
        background: #ffed4e;
    }
    
    .product-cta-btn i {
        font-size: 0.9rem;
        transition: transform 0.2s ease;
    }
    
    .product-cta-btn:hover i {
        transform: translateX(2px);
    }
    
    
    
    @media (max-width: 1400px) {
        .pro-controls-bar {
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        .controls-left,
        .controls-right {
            width: 100%;
            justify-content: space-between;
        }
    }
    
    @media (max-width: 1200px) {
        .product-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .search-container {
            flex-direction: column;
            gap: 1rem;
        }
        
        .search-input-wrapper {
            max-width: 100%;
        }
        
        .quick-filters {
            justify-content: center;
        }
    }
    
    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .pro-product-card {
            display: flex;
            flex-direction: row;
            height: auto;
        }
        
        .product-image-container {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
            border-radius: 8px;
        }
        
        .product-info {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-title {
            font-size: 1.1rem;
            -webkit-line-clamp: 1;
            line-clamp: 1;
        }
        
        .product-specifications {
            margin-bottom: 1rem;
        }
        
        .spec-item {
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            padding: 0.25rem 0;
        }
        
        .product-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        
        .product-price-container {
            text-align: center;
        }
        
        .product-cta-btn {
            justify-content: center;
        }
        
        .product-overlay {
            display: none;
        }
        
        
        .pro-controls-bar {
            padding: 1rem;
        }
        
        .controls-left,
        .controls-right {
            flex-direction: column;
            gap: 1rem;
        }
        
        .results-summary {
            justify-content: center;
        }
        
        .search-hero {
            padding: 1.5rem;
        }
        
        .quick-filter-tags {
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .pro-product-card {
            flex-direction: column;
        }
        
        .product-image-container {
            width: 100%;
            height: 160px;
            border-radius: 8px;
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .search-hero {
            padding: 1rem;
        }
        
        .search-input-wrapper input {
            padding: 1rem 1rem 1rem 3rem;
            font-size: 1rem;
        }
        
        .search-btn {
            padding: 1rem 1.5rem;
            font-size: 1rem;
        }
        
        .quick-tag {
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
        }
        
        .pro-controls-bar {
            padding: 0.75rem;
        }
        
        .view-controls {
            width: 100%;
        }
        
        .view-btn {
            flex: 1;
        }
    }
    
    
    .notification {
        position: fixed;
        top: 2rem;
        right: 2rem;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        color: var(--text-light);
        font-weight: 500;
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        backdrop-filter: var(--pro-blur);
        box-shadow: var(--pro-shadow);
        transform: translateX(400px);
        opacity: 0;
        transition: var(--pro-transition);
        max-width: 400px;
    }
    
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification-success {
        border-color: var(--pro-success);
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), var(--pro-surface));
    }
    
    .notification-success i {
        color: var(--pro-success);
    }
    
    .notification-warning {
        border-color: var(--pro-warning);
        background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), var(--pro-surface));
    }
    
    .notification-warning i {
        color: var(--pro-warning);
    }
    
    .notification-error {
        border-color: var(--pro-error);
        background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), var(--pro-surface));
    }
    
    .notification-error i {
        color: var(--pro-error);
    }
    
    .notification-info {
        border-color: var(--pro-info);
        background: linear-gradient(135deg, rgba(33, 150, 243, 0.1), var(--pro-surface));
    }
    
    .notification-info i {
        color: var(--pro-info);
    }
    
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    
    .product-grid.list-view {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .product-grid.list-view .pro-product-card {
        display: flex !important;
        flex-direction: row !important;
        height: auto !important;
        min-height: 60px !important;
        align-items: stretch !important;
    }
    
    .product-grid.list-view .product-image-container {
        width: 60px !important;
        height: 60px !important;
        flex-shrink: 0 !important;
    }
    
    .product-grid.list-view .product-info {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        padding: 0.5rem !important;
    }
    
    .product-grid.list-view .product-header {
        margin-bottom: 0.1rem;
    }
    
    .product-grid.list-view .product-title {
        font-size: 1rem;
        -webkit-line-clamp: 1;
        line-clamp: 1;
    }
    
    .product-grid.list-view .product-specifications {
        margin-bottom: 0.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .product-grid.list-view .spec-item {
        margin-bottom: 0;
        font-size: 0.8rem;
        border-bottom: none;
        padding: 0;
    }
    
    .product-grid.list-view .product-footer {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        margin-top: auto;
    }
    
    .product-grid.list-view .product-price-container {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 1rem;
    }
    
    .product-grid.list-view .product-price {
        font-size: 1.4rem;
    }
    
    .product-grid.list-view .product-actions {
        display: flex;
        align-items: center;
    }
    
    .product-grid.list-view .product-cta-btn {
        min-width: 100px;
        padding: 0.4rem 0.8rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .product-grid.list-view .product-overlay {
        display: none;
    }
    
    .product-grid.compact-view {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .product-grid.compact-view .pro-product-card {
        padding: 0;
    }
    
    .product-grid.compact-view .product-image-container {
        height: 120px;
    }
    
    .product-grid.compact-view .product-info {
        padding: 1rem;
    }
    
    .product-grid.compact-view .product-title {
        font-size: 1rem;
        -webkit-line-clamp: 1;
        line-clamp: 1;
    }
    
    .product-grid.compact-view .product-specifications {
        margin-bottom: 1rem;
    }
    
    .product-grid.compact-view .spec-item {
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
        padding: 0.25rem 0;
    }
    
    .product-grid.compact-view .product-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    
    .product-grid.compact-view .product-price {
        font-size: 1.2rem;
    }
    
    .product-grid.compact-view .product-cta-btn {
        padding: 0.75rem 1.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex !important;
        visibility: visible !important;
        min-width: 140px;
        color: #000 !important;
        overflow: visible !important;
    }
    
    .product-grid.compact-view .product-cta-btn span,
    .product-grid.compact-view .product-cta-btn i {
        color: #000 !important;
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative;
        z-index: 10;
    }

    .no-results-message {
        grid-column: 1 / -1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 3rem 1rem;
        text-align: center;
    }

    .no-results-content {
        max-width: 400px;
        padding: 2rem;
        background: var(--pro-surface);
        border: 1px solid var(--pro-border);
        border-radius: 16px;
        box-shadow: var(--pro-shadow);
    }

    .no-results-content i {
        font-size: 3rem;
        color: var(--pro-secondary);
        margin-bottom: 1rem;
    }

    .no-results-content h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pro-text);
        margin-bottom: 0.5rem;
    }

    .no-results-content p {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .clear-search-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: var(--pro-primary);
        color: #000;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--pro-transition);
    }

    .clear-search-btn:hover {
        background: var(--pro-secondary);
        transform: translateY(-2px);
    }

    .search-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(42, 42, 42, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 8px;
    }

    .search-loading-content {
        text-align: center;
        color: var(--pro-text);
    }

    .search-loading i {
        color: var(--pro-primary);
    }
    
    .product-grid.compact-view .product-overlay {
        display: none;
    }
    
    
    
    .pro-product-card:focus-within {
        outline: 2px solid #ffd700;
        outline-offset: 2px;
    }
    
    
    .modern-controls-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 215, 0, 0.1);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        backdrop-filter: blur(10px);
    }
    
    .results-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .results-count,
    .active-filters-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-light);
    }
    
    .results-count i,
    .active-filters-count i {
        color: var(--accent-primary);
        font-size: 0.8rem;
    }
    
    .results-count span:first-of-type {
        font-weight: 700;
        color: var(--accent-primary);
        font-size: 1.1rem;
    }
    
    .active-filters-count span:first-of-type {
        font-weight: 600;
        color: #FF9800;
    }
    
    .results-text {
        color: var(--text-secondary);
    }
    
    .controls-actions {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .sort-control {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .sort-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--text-light);
        font-weight: 500;
        cursor: pointer;
    }
    
    .sort-label i {
        color: var(--accent-primary);
        font-size: 0.8rem;
    }
    
    .sort-select {
        padding: 0.6rem 1rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 8px;
        color: var(--text-light);
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 150px;
    }
    
    .sort-select:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.1);
    }
    
    .sort-select option {
        background: var(--bg-dark);
        color: var(--text-light);
    }
    
    .view-controls {
        display: flex;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .view-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: transparent;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .view-btn:not(:last-child) {
        border-right: 1px solid rgba(255, 215, 0, 0.2);
    }
    
    .view-btn:hover {
        color: var(--text-light);
        background: rgba(255, 215, 0, 0.1);
    }
    
    .view-btn.active {
        color: var(--accent-primary);
        background: rgba(255, 215, 0, 0.15);
    }
    
    .view-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--accent-primary);
    }
    
    @media (max-width: 768px) {
        .modern-controls-bar {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }
        
        .results-info {
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .controls-actions {
            width: 100%;
            justify-content: space-between;
        }
        
        .sort-control {
            flex: 1;
        }
        
        .sort-select {
            flex: 1;
            min-width: auto;
        }
    }
    
    @media (max-width: 480px) {
        .results-info {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }
        
        .controls-actions {
            flex-direction: column;
            gap: 1rem;
        }
        
        .sort-control {
            width: 100%;
        }
        
        .view-controls {
            align-self: center;
        }
    }
    
    
    @media (max-width: 768px) {
        .product-grid.list-view .pro-product-card {
            flex-direction: column;
            min-height: auto;
        }
        
        .product-grid.list-view .product-image-container {
            width: 100%;
            height: 150px;
        }
        
        .product-grid.list-view .product-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        
        .product-grid.list-view .product-price-container {
            flex-direction: row;
            justify-content: center;
        }
        
        .product-grid.list-view .product-actions {
            justify-content: center;
        }
        
        .product-grid.list-view .product-cta-btn {
            width: 100%;
            min-width: auto;
        }
    }
    
    
    @media (max-width: 1200px) {
        .modern-filters-container {
            padding: 1rem;
        }
        
        .filters-row {
            gap: 1rem;
        }
    }
    
    @media (max-width: 768px) {
        .modern-filters-container {
            margin-bottom: 1rem;
        }
        
        .search-input-container {
            max-width: 100%;
        }
        
        .search-input-container input {
            padding: 0.875rem 0.875rem 0.875rem 2.5rem;
            font-size: 0.95rem;
        }
        
        .search-icon {
            left: 0.875rem;
            font-size: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .modern-filters-container {
            padding: 0.75rem;
        }
        
        .filter-group {
            gap: 0.25rem;
        }
        
        .filter-label {
            font-size: 0.8rem;
        }
        
        .filter-select {
            padding: 0.7rem 0.8rem;
            font-size: 0.9rem;
        }
        
        .checkbox-filters {
            gap: 0.75rem;
        }
        
        .checkbox-filter {
            padding: 0.4rem;
        }
        
        .checkmark {
            width: 16px;
            height: 16px;
        }
        
        .checkbox-label {
            font-size: 0.85rem;
        }
        
        .filter-btn {
            padding: 0.7rem 1rem;
            font-size: 0.85rem;
        }
    }
    </style>
</head>

<body>
    <header id="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.php"><img src="./assets/images/logobg.png" alt="Logo Ouipneu.fr"></a>
            </div>
            <nav class="main-nav">
                <ul id="main-nav-links">
                    <li><a href="index.php" aria-label="Accueil">Accueil</a></li>
                    <li><a href="produits.php" class="active" aria-label="Nos Pneus">Nos Pneus</a></li>
                    <li><a href="contact.php" aria-label="Contact">Contact</a></li>
                    <li><a href="index.php#about-us" aria-label="À propos de nous">À Propos</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <form class="search-bar" role="search">
                    <input type="search" placeholder="Rechercher des pneus..." aria-label="Rechercher des pneus">
                    <button type="submit" aria-label="Lancer la recherche"><i class="fas fa-search"></i></button>
                </form>
                <div class="account-icon">
                    <?php if (isset($_SESSION['id_utilisateur'])): ?>
                        <a href="dashboard.php" aria-label="Mon Compte"><i class="fas fa-user-circle"></i></a>
                    <?php else: ?>
                        <a href="login.php" aria-label="Mon Compte"><i class="fas fa-user-circle"></i></a>
                    <?php endif; ?>
                </div>
                <div class="cart-icon">
                    <a href="panier.php" aria-label="Panier"><i class="fas fa-shopping-cart"></i><span class="cart-item-count"><?php echo array_sum($_SESSION['panier'] ?? []); ?></span></a>
                </div>
                <button type="button" id="mobile-search-toggle-button" class="mobile-search-toggle" aria-label="Ouvrir la recherche" aria-expanded="false">
                    <i class="fas fa-search"></i>
                </button>
                <button id="hamburger-button" class="hamburger-button" aria-label="Ouvrir le menu" aria-expanded="false">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </header>

    <style>
.no-results {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.no-results-icon {
    font-size: 3rem;
    color: var(--accent-primary);
    margin-bottom: 1rem;
}

.no-results-text h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.no-results-text p {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.error-message {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
    background: var(--bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.error-icon {
    font-size: 3rem;
    color: #e74c3c;
    margin-bottom: 1rem;
}

.error-text {
    color: var(--text-primary);
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 1.2rem;
}

.error-support {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.search-loading {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

.search-loading i {
    color: var(--accent-primary);
    margin-bottom: 1rem;
}

.product-card.search-result {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
    </style>
    <main class="site-main-content">
        <section id="all-products" class="all-products-section section-padding">
            <div class="container" style="padding: 0 0.75rem;">
                <h1 class="page-title" data-aos="fade-up">Tous Nos Pneus</h1>
                <p class="section-intro product-page-intro" data-aos="fade-up" data-aos-delay="50">
                    Explorez notre catalogue complet de pneus. Utilisez les filtres pour affiner votre recherche et trouver le pneu parfait pour votre véhicule, quelle que soit la saison ou votre style de conduite.
                </p>

                <!-- Barre de recherche ultra-pro -->
                <div class="pro-search-section" data-aos="fade-up" data-aos-delay="100">
                    <div class="search-hero">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <i class="fas fa-search search-icon"></i>
                                <input type="search" id="product-search-input" placeholder="Rechercher un pneu par nom, marque, dimension..." aria-label="Rechercher un pneu" autocomplete="off">
                                <div class="search-suggestions" id="search-suggestions"></div>
                            </div>
                            <button type="button" class="search-btn" id="search-btn">
                                <i class="fas fa-search"></i>
                                <span>Rechercher</span>
                    </button>
                </div>

                        <!-- Filtres rapides -->
                        <div class="quick-filters">
                            <span class="quick-filter-label">Filtres rapides :</span>
                            <div class="quick-filter-tags">
                                <button class="quick-tag" data-filter="runflat">
                                    <i class="fas fa-shield-alt"></i>
                                    Runflat
                                </button>
                                <button class="quick-tag" data-filter="winter">
                                    <i class="fas fa-snowflake"></i>
                                    Hiver
                                </button>
                                <button class="quick-tag" data-filter="summer">
                                    <i class="fas fa-sun"></i>
                                    Été
                                </button>
                                <button class="quick-tag" data-filter="all-season">
                                    <i class="fas fa-calendar-alt"></i>
                                    Toutes saisons
                                </button>
                    </div>
                                    </div>
                                    </div>
                </div>

                <!-- Filtres en ligne modernisés -->
                <div class="modern-filters-container" data-aos="fade-up" data-aos-delay="150">
                    <form id="product-filters-form" class="modern-filters-form">
                        <div class="filters-row">
                            <!-- Dimensions -->
                            <div class="filter-group">
                                <label class="filter-label">Dimensions</label>
                                <div class="dimensions-inputs">
                                    <select id="filter-width" name="width" class="filter-select">
                                        <option value="">Largeur</option>
                                    </select>
                                    <span class="dimension-separator">/</span>
                                    <select id="filter-ratio" name="ratio" class="filter-select">
                                        <option value="">Ratio</option>
                                    </select>
                                    <span class="dimension-separator">R</span>
                                    <select id="filter-diameter" name="diameter" class="filter-select">
                                        <option value="">Diamètre</option>
                                        </select>
                                </div>
                            </div>

                            <!-- Marque -->
                            <div class="filter-group">
                                <label class="filter-label">Marque</label>
                                <select id="filter-brand" name="brand" class="filter-select">
                                    <option value="">Toutes les marques</option>
                                </select>
                            </div>

                            <!-- Saison -->
                            <div class="filter-group">
                                <label class="filter-label">Saison</label>
                                <select id="filter-type" name="type" class="filter-select">
                                    <option value="">Toutes saisons</option>
                                    <option value="Été">Été</option>
                                    <option value="Hiver">Hiver</option>
                                    <option value="Toutes Saisons">Toutes Saisons</option>
                                </select>
                            </div>

                            <!-- Caractéristiques -->
                            <div class="filter-group features-group">
                                <label class="filter-label">Caractéristiques</label>
                                <div class="checkbox-filters">
                                    <label class="checkbox-filter">
                                    <input type="checkbox" id="filter-runflat" name="runflat" value="true">
                                        <span class="checkmark"></span>
                                        <span class="checkbox-label">Runflat</span>
                                    </label>
                                    <label class="checkbox-filter">
                                    <input type="checkbox" id="filter-reinforced" name="reinforced" value="true">
                                        <span class="checkmark"></span>
                                        <span class="checkbox-label">Renforcé</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="filter-actions">
                                <button type="button" id="reset-filters-button" class="filter-btn secondary">
                                    <i class="fas fa-undo"></i>
                                    <span>Réinitialiser</span>
                                </button>
                                <button type="button" id="apply-filters-button" class="filter-btn primary">
                                    <i class="fas fa-filter"></i>
                                    <span>Filtrer</span>
                                </button>
                            </div>
                            </div>
                        </form>
                    </div>

                <!-- Contrôles ultra-pro -->
                <div class="pro-controls-bar" data-aos="fade-up" data-aos-delay="200">
                    <div class="controls-left">
                        <div class="results-summary">
                            <div class="results-count">
                                <i class="fas fa-cube"></i>
                                <span id="product-results-count"><?php echo $total_produits; ?></span>
                                <span class="results-text">produits</span>
                    </div>
                            <?php if (!empty($active_filters_for_display)): ?>
                                <div class="active-filters-badge">
                                    <i class="fas fa-filter"></i>
                                    <span><?php echo count($active_filters_for_display); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <div class="controls-right">
                        <div class="sort-section">
                            <label for="sort-by" class="sort-label">
                                <i class="fas fa-sort-amount-down"></i>
                                Trier par
                            </label>
                            <select id="sort-by" name="sort_by" class="sort-select">
                            <option value="relevance">Pertinence</option>
                                <option value="price-asc">Prix croissant</option>
                                <option value="price-desc">Prix décroissant</option>
                                <option value="name-asc">Nom A-Z</option>
                                <option value="name-desc">Nom Z-A</option>
                                <option value="rating">Note</option>
                        </select>
                        </div>
                        
                        <div class="view-section">
                            <div class="view-controls">
                                <button type="button" class="view-btn active" data-view="grid" title="Vue grille">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="view-btn" data-view="list" title="Vue liste">
                                    <i class="fas fa-list"></i>
                                </button>
                                <button type="button" class="view-btn" data-view="compact" title="Vue compacte">
                                    <i class="fas fa-th-large"></i>
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </div>


                <div class="product-grid" id="product-grid">
                    <?php if (empty($pneus)): ?>
                        <p>Aucun pneu trouvé pour les critères sélectionnés.</p>
                    <?php else: ?>
                        <?php foreach ($pneus as $index => $pneu): ?>
                            <?php
                            $parsed_specs_for_data = parseProductSpecifications($pneu['specifications']);
                            $display_details = getProductDisplayDetails($pneu);
                            $marque_nom_pour_data = extractBrandFromName($pneu['nom']);
                            $taille_parsed = parseTireSize($pneu['taille']);
                            $prix_pour_data = convertPriceToFloat($pneu['prix']);
                            ?>
                            <div class="pro-product-card"
                                data-aos="fade-up"
                                data-aos-duration="500"
                                data-aos-delay="<?php echo ($index % 4 + 1) * 50; ?>"
                                data-aos-once="true"
                                data-product-id="<?php echo $pneu['id']; ?>"
                                data-runflat="<?php echo $parsed_specs_for_data['is_runflat'] ? 'true' : 'false'; ?>"
                                data-reinforced="<?php echo $parsed_specs_for_data['is_reinforced'] ? 'true' : 'false'; ?>"
                                data-brand="<?php echo sanitize_html_output($marque_nom_pour_data); ?>"
                                data-name="<?php echo sanitize_html_output($pneu['nom']); ?>"
                                data-price="<?php echo $prix_pour_data; ?>"
                                data-type="<?php echo sanitize_html_output($pneu['saison']); ?>"
                                data-width="<?php echo sanitize_html_output($taille_parsed['data_width']); ?>"
                                data-ratio="<?php echo sanitize_html_output($taille_parsed['data_ratio']); ?>"
                                data-diameter="<?php echo sanitize_html_output($taille_parsed['data_diameter']); ?>"
                                data-product-search="<?php echo htmlspecialchars(strtolower($pneu['nom'] . ' ' . $pneu['taille'] . ' ' . $pneu['saison'] . ' ' . $marque_nom_pour_data . ' ' . str_replace('.', ',', $pneu['prix']) . ' ' . ($pneu['specifications'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
                                
                                
                                <!-- Image et badges -->
                                <div class="product-image-container">
                                    <img loading="lazy" 
                                         src="<?php echo sanitize_html_output(!empty($pneu['image']) ? $pneu['image'] : 'https://placehold.co/400x300/121212/ffdd03?text=Image+Indisponible'); ?>" 
                                         alt="<?php echo sanitize_html_output($pneu['nom']); ?>" 
                                         class="product-image">
                                    
                                    <!-- Badges améliorés -->
                                    <div class="product-badges">
                                    <?php echo $display_details['badge_html']; ?>
                                        <?php if ($parsed_specs_for_data['is_runflat']): ?>
                                            <span class="product-badge runflat-badge">
                                                <i class="fas fa-shield-alt"></i>
                                                Runflat
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($parsed_specs_for_data['is_reinforced']): ?>
                                            <span class="product-badge reinforced-badge">
                                                <i class="fas fa-weight-hanging"></i>
                                                XL
                                            </span>
                                        <?php endif; ?>
                                </div>
                                    
                                    <!-- Overlay avec actions -->
                                    <div class="product-overlay">
                                        <div class="overlay-actions">
                                            <a href="produit.php?id=<?php echo $pneu['id']; ?>" class="overlay-btn primary">
                                                <i class="fas fa-eye"></i>
                                                <span>Voir détails</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contenu de la carte -->
                                <div class="product-info">
                                    <div class="product-header">
                                        <div class="product-brand"><?php echo sanitize_html_output($marque_nom_pour_data); ?></div>
                                        <h3 class="product-title"><?php echo sanitize_html_output($pneu['nom']); ?></h3>
                                    </div>
                                    
                                    <div class="product-specifications">
                                        <div class="spec-item">
                                            <i class="fas fa-ruler-combined"></i>
                                            <span><?php echo sanitize_html_output($pneu['taille']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <i class="fas fa-snowflake"></i>
                                            <span><?php echo sanitize_html_output($pneu['saison']); ?></span>
                                        </div>
                                        <?php if (!empty($pneu['specifications'])):
                                            $specs_text_to_display = trim($pneu['specifications']);
                                            if (!empty($specs_text_to_display)): ?>
                                                <div class="spec-item">
                                                    <i class="fas fa-cog"></i>
                                                    <span><?php echo sanitize_html_output($specs_text_to_display); ?></span>
                                                </div>
                                            <?php endif;
                                        endif; ?>
                                    </div>
                                    
                                    <div class="product-footer">
                                        <div class="product-price-container">
                                            <span class="product-price"><?php echo sanitize_html_output($pneu['prix']); ?></span>
                                            <span class="product-stock <?php echo $display_details['stock_class']; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?php echo sanitize_html_output($display_details['stock_text']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="product-actions">
                                            <a href="produit.php?id=<?php echo $pneu['id']; ?>" class="product-cta-btn">
                                                <span>Voir détails</span>
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="infinite-scroll-loader" style="display: none; text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--accent-primary);"></i>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Chargement de plus de pneus...</p>
                </div>

                <!-- Pagination simple (comme l'admin) -->
                <div style="text-align: center; margin-top: 2rem; color: var(--text-secondary);">
                    <p><?php echo $total_produits; ?> produits au total</p>
                </div>
            </div>
        </section>
    </main>

    <footer id="main-footer">
        <div class="container" style="padding: 0 0.75rem;">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>Ouipneu.fr</h3>
                    <p>Votre partenaire de confiance pour des pneus premium, un montage expert et un service client exceptionnel.</p>
                </div>
                <div class="footer-column">
                    <h3>Navigation</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="index.php#promotions">Promotions</a></li>
                        <li><a href="contact.php">Contactez-nous</a></li>
                        <li><a href="dashboard.php">Mon Compte</a></li>
                        <li><a href="devenir_partenaire.php">Devenir Garage Partenaire</a></li>
                        <li><a href="nos_garages_partenaires.php">Nos Garages Partenaires</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Informations</h3>
                    <ul>
                        <li><a href="legal-notice.php">Mentions Légales</a></li>
                        <li><a href="privacy-policy.php">Politique de Confidentialité</a></li>
                        <li><a href="cgv.php">Conditions Générales de Vente</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Suivez-Nous</h3>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span id="current-year"><?php echo date('Y'); ?></span> Ouipneu.fr. Tous droits réservés. <span style="margin-left: 10px;">|</span> <a href="admin_login.php" style="font-size: 0.8em; color: var(--text-secondary);">Admin</a></p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="js/main.js"></script>
   <script>
    document.addEventListener('DOMContentLoaded', function() {
    const mainSearchInput = document.getElementById('product-search-input');
    const productGridContainer = document.getElementById('product-grid');
    const productResultsCount = document.getElementById('product-results-count');
    
    let searchTimeout = null;
    let lastValue = '';
    let isSearching = false;

    function showLoader() {
        const existingLoader = productGridContainer.querySelector('.search-loading');
        if (!existingLoader) {
            const loader = document.createElement('div');
            loader.className = 'search-loading';
            loader.innerHTML = `
                <div class="search-loading-content">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p style="margin-top: 1rem;">Recherche en cours...</p>
                </div>
            `;
            productGridContainer.appendChild(loader);
        }
    }
    
    function hideLoader() {
        const loader = productGridContainer.querySelector('.search-loading');
        if (loader) {
            loader.remove();
        }
    }

    function updateResultsCount(count) {
        if (productResultsCount) {
            productResultsCount.textContent = count;
        }
    }

    function normalizeText(str) {
        return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9,./\s]/gi, '');
    }

    function detectTireSizeFormat(input) {
        const parts = input.match(/\d{3}[\s/-]?\d{2}[\s/-]?\d{2}/);
        if (!parts) return null;

        const match = parts[0].match(/(\d{3})[\s/-]?(\d{2})[\s/-]?(\d{2})/);
        if (match) {
            return `${match[1]}/${match[2]}R${match[3]}`;
        }
        return null;
    }

    function performSearch(query) {
        if (query === lastValue || isSearching) {
            return;
        }
        
        lastValue = query;
        isSearching = true;

        showLoader();

        const allProductCards = productGridContainer.querySelectorAll('.pro-product-card');
        let visibleCount = 0;

        const inputValue = normalizeText(query.trim());
        const searchTerms = inputValue.split(/\s+/).filter(Boolean);
        const normalizedSize = detectTireSizeFormat(query);

        allProductCards.forEach((card, index) => {
            card.classList.remove('search-result');
            
            const dataset = normalizeText(card.dataset.productSearch || '');
            const taille = normalizeText(card.querySelector('.product-specifications .spec-item:first-child span')?.textContent || '');
            
            let matchSearch = true;
            if (normalizedSize) {
                matchSearch = taille === normalizeText(normalizedSize);
            } else if (searchTerms.length > 0) {
                matchSearch = searchTerms.every(term => dataset.includes(term));
            }
            
            if (matchSearch) {
                card.style.display = 'block';
                card.classList.add('search-result');
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.classList.remove('search-result');
            }
        });

        updateResultsCount(visibleCount);

        if (visibleCount === 0 && query.trim()) {
            const noResultsMessage = document.createElement('div');
            noResultsMessage.className = 'no-results-message';
            noResultsMessage.innerHTML = `
                <div class="no-results-content">
                    <i class="fas fa-search"></i>
                    <h3>Aucun résultat trouvé</h3>
                    <p>Aucun pneu ne correspond à votre recherche "<strong>${query}</strong>"</p>
                    <button class="clear-search-btn" data-action="clear-search">
                        <i class="fas fa-times"></i>
                        Effacer la recherche
                    </button>
                </div>
            `;
            
            const existingMessage = productGridContainer.querySelector('.no-results-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            productGridContainer.appendChild(noResultsMessage);
            
            const clearBtn = noResultsMessage.querySelector('.clear-search-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    if (mainSearchInput) {
                        mainSearchInput.value = '';
                        performSearch('');
                    }
                });
            }
        } else {
            const existingMessage = productGridContainer.querySelector('.no-results-message');
            if (existingMessage) {
                existingMessage.remove();
            }
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (query.trim()) {
            urlParams.set('search', query.trim());
        } else {
            urlParams.delete('search');
        }
        urlParams.delete('page');
        
        const newUrl = window.location.pathname + '?' + urlParams.toString();
        window.history.replaceState({}, '', newUrl);

        if (window.AOS) {
            AOS.refresh();
        }

        hideLoader();

        isSearching = false;
    }

    if (mainSearchInput) {
        const allCards = productGridContainer.querySelectorAll('.pro-product-card');

        const urlParams = new URLSearchParams(window.location.search);
        const searchFromUrl = urlParams.get('search');
        if (searchFromUrl) {
            mainSearchInput.value = searchFromUrl;
            lastValue = searchFromUrl;
            performSearch(searchFromUrl);
        }

        mainSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            if (!query) {
                searchTimeout = setTimeout(() => performSearch(query), 100);
            } else {
                searchTimeout = setTimeout(() => performSearch(query), 300);
            }
        });

        mainSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                performSearch(this.value.trim());
            }
        });
    }

    const headerSearchForm = document.querySelector('header .search-bar');
    if (headerSearchForm) {
        headerSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const headerSearchInput = this.querySelector('input[type="search"]');
            if (headerSearchInput && mainSearchInput) {
                mainSearchInput.value = headerSearchInput.value;
                mainSearchInput.focus();
                performSearch(headerSearchInput.value.trim());
            }
        });
    }

    
    const viewButtons = document.querySelectorAll('.view-btn');
    const productGridElement = document.querySelector('.product-grid');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            productGridElement.className = 'product-grid';
            if (view !== 'grid') {
                productGridElement.classList.add(`${view}-view`);
            }
            
            localStorage.setItem('productView', view);
            
            productGridElement.style.opacity = '0.5';
            setTimeout(() => {
                productGridElement.style.opacity = '1';
            }, 200);
        });
    });
    
    const savedView = localStorage.getItem('productView');
    if (savedView) {
        const savedButton = document.querySelector(`[data-view="${savedView}"]`);
        if (savedButton) {
            savedButton.click();
        }
    }
    
    
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    const searchBtn = document.getElementById('search-btn');
    
    if (searchBtn && mainSearchInput) {
        searchBtn.addEventListener('click', function() {
            const query = mainSearchInput.value.trim();
            performSearch(query);
        });
    }
    
    const quickTags = document.querySelectorAll('.quick-tag');
    
    quickTags.forEach(tag => {
        tag.addEventListener('click', function() {
            this.classList.toggle('active');
            
            const filter = this.dataset.filter;
            applyQuickFilter(filter, this.classList.contains('active'));
        });
    });
    
    function applyQuickFilter(filter, isActive) {
        const cards = document.querySelectorAll('.pro-product-card');
        
        cards.forEach(card => {
            let shouldShow = true;
            
            if (isActive) {
                switch(filter) {
                    case 'runflat':
                        shouldShow = card.dataset.runflat === 'true';
                        break;
                    case 'winter':
                        shouldShow = card.dataset.type === 'Hiver';
                        break;
                    case 'summer':
                        shouldShow = card.dataset.type === 'Été';
                        break;
                    case 'all-season':
                        shouldShow = card.dataset.type === 'Toutes Saisons';
                        break;
                }
            }
            
            if (shouldShow) {
                card.style.display = 'block';
                card.style.animation = 'fadeInUp 0.5s ease';
            } else {
                card.style.display = 'none';
            }
        });
        
        const visibleCards = document.querySelectorAll('.pro-product-card[style*="block"], .pro-product-card:not([style*="none"])');
        const resultsCount = document.getElementById('product-results-count');
        if (resultsCount) {
            resultsCount.textContent = visibleCards.length;
        }
    }
    
    
    
    
    const modernFiltersForm = document.getElementById('product-filters-form');
    const applyFiltersBtn = document.getElementById('apply-filters-button');
    const resetFiltersBtn = document.getElementById('reset-filters-button');
    
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Filtrage...</span>';
            this.disabled = true;
            
            setTimeout(() => {
                modernFiltersForm.submit();
            }, 500);
        });
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Réinitialisation...</span>';
            this.disabled = true;
            
            const formInputs = modernFiltersForm.querySelectorAll('input, select');
            formInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            
            setTimeout(() => {
                modernFiltersForm.submit();
            }, 300);
        });
    }
    
    const filterInputs = modernFiltersForm.querySelectorAll('select, input[type="checkbox"]');
    let filterTimeout;
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (filterTimeout) {
                clearTimeout(filterTimeout);
            }
            
            filterTimeout = setTimeout(() => {
                modernFiltersForm.submit();
            }, 1000);
        });
    });
    
    const sortSelect = document.getElementById('sort-by');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const productCards = document.querySelectorAll('.pro-product-card');
            productCards.forEach((card, index) => {
                card.style.opacity = '0.5';
                card.style.transform = 'scale(0.95)';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, index * 50);
            });
            
 de tri
            const form = document.createElement('form');
            form.method = 'GET';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sort_by';
            input.value = this.value;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.pro-product-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        cardObserver.observe(card);
    });
    
    if (viewButtons.length === 0) {
        console.log('Boutons de vue non trouvés - fonctionnalité désactivée');
    }
    
    if (!productGridElement) {
        console.log('Grille de produits non trouvée - fonctionnalité désactivée');
    }
});
   </script>
</body>

</html>