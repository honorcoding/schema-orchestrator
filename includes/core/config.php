<?php
/**
 * PLUGIN CORE 
 */

require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-registry.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-overrides.php';
require_once SCHEMA_ORCHESTRATOR_PATH . 'includes/core/class-schema-orchestrator.php';
\Schema_Orchestrator\Schema_Orchestrator::instance();

