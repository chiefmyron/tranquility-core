<?php

namespace Tranquility\Enum\System;

/**
 * Defines the valid transactions sources that may be used in an audit trail record
 *   UI_Frontend
 *   UI_Backend
 *   Batch
 *   API_v1
 */

class TransactionSource extends \Tranquility\Enum {
    
    const UI_Frontend = 'audit_transaction_source_frontend_ui';
    const UI_Backend = 'audit_transaction_source_backend_ui';
    const Batch = 'audit_transaction_source_batch';
    const API_v1 = 'audit_transaction_source_api_v1';
    
}