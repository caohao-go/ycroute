
/* This file was generated automatically by Zephir do not modify it! */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>

#include "php_ext.h"
#include "phalcon.h"

#include <ext/standard/info.h>

#include <Zend/zend_operators.h>
#include <Zend/zend_exceptions.h>
#include <Zend/zend_interfaces.h>

#include "kernel/globals.h"
#include "kernel/main.h"
#include "kernel/fcall.h"
#include "kernel/memory.h"



zend_class_entry *phalcon_db_adapterinterface_ce;
zend_class_entry *phalcon_events_eventsawareinterface_ce;
zend_class_entry *phalcon_db_dialectinterface_ce;
zend_class_entry *phalcon_db_columninterface_ce;
zend_class_entry *phalcon_db_indexinterface_ce;
zend_class_entry *phalcon_db_referenceinterface_ce;
zend_class_entry *phalcon_db_resultinterface_ce;
zend_class_entry *phalcon_events_eventinterface_ce;
zend_class_entry *phalcon_events_managerinterface_ce;
zend_class_entry *phalcon_db_adapter_ce;
zend_class_entry *phalcon_exception_ce;
zend_class_entry *phalcon_db_adapter_pdo_ce;
zend_class_entry *phalcon_db_dialect_ce;
zend_class_entry *phalcon_db_adapter_pdo_mysql_ce;
zend_class_entry *phalcon_db_ce;
zend_class_entry *phalcon_db_column_ce;
zend_class_entry *phalcon_db_dialect_mysql_ce;
zend_class_entry *phalcon_db_exception_ce;
zend_class_entry *phalcon_db_index_ce;
zend_class_entry *phalcon_db_profiler_ce;
zend_class_entry *phalcon_db_profiler_item_ce;
zend_class_entry *phalcon_db_rawvalue_ce;
zend_class_entry *phalcon_db_reference_ce;
zend_class_entry *phalcon_db_result_pdo_ce;
zend_class_entry *phalcon_events_event_ce;
zend_class_entry *phalcon_events_exception_ce;
zend_class_entry *phalcon_events_manager_ce;
zend_class_entry *phalcon_kernel_ce;

ZEND_DECLARE_MODULE_GLOBALS(phalcon)

PHP_INI_BEGIN()
	STD_PHP_INI_BOOLEAN("phalcon.db.escape_identifiers", "1", PHP_INI_ALL, OnUpdateBool, db.escape_identifiers, zend_phalcon_globals, phalcon_globals)
	STD_PHP_INI_BOOLEAN("phalcon.db.force_casting", "0", PHP_INI_ALL, OnUpdateBool, db.force_casting, zend_phalcon_globals, phalcon_globals)
PHP_INI_END()

static PHP_MINIT_FUNCTION(phalcon)
{
	REGISTER_INI_ENTRIES();
	zephir_module_init();
	
	ZEPHIR_INIT(Phalcon_Db_AdapterInterface);
	ZEPHIR_INIT(Phalcon_Events_EventsAwareInterface);
	ZEPHIR_INIT(Phalcon_Db_DialectInterface);
	ZEPHIR_INIT(Phalcon_Db_ColumnInterface);
	ZEPHIR_INIT(Phalcon_Db_IndexInterface);
	ZEPHIR_INIT(Phalcon_Db_ReferenceInterface);
	ZEPHIR_INIT(Phalcon_Db_ResultInterface);
	ZEPHIR_INIT(Phalcon_Events_EventInterface);
	ZEPHIR_INIT(Phalcon_Events_ManagerInterface);
	ZEPHIR_INIT(Phalcon_Db_Adapter);
	ZEPHIR_INIT(Phalcon_Exception);
	ZEPHIR_INIT(Phalcon_Db_Adapter_Pdo);
	ZEPHIR_INIT(Phalcon_Db_Dialect);
	ZEPHIR_INIT(Phalcon_Db);
	ZEPHIR_INIT(Phalcon_Db_Adapter_Pdo_Mysql);
//	ZEPHIR_INIT(Phalcon_Db_Column);
	ZEPHIR_INIT(Phalcon_Db_Dialect_Mysql);
	ZEPHIR_INIT(Phalcon_Db_Exception);
	ZEPHIR_INIT(Phalcon_Db_Index);
	ZEPHIR_INIT(Phalcon_Db_Profiler);
	ZEPHIR_INIT(Phalcon_Db_Profiler_Item);
	ZEPHIR_INIT(Phalcon_Db_RawValue);
	ZEPHIR_INIT(Phalcon_Db_Reference);
	ZEPHIR_INIT(Phalcon_Db_Result_Pdo);
	ZEPHIR_INIT(Phalcon_Events_Event);
	ZEPHIR_INIT(Phalcon_Events_Exception);
	ZEPHIR_INIT(Phalcon_Events_Manager);
	ZEPHIR_INIT(Phalcon_Kernel);
	return SUCCESS;
}

#ifndef ZEPHIR_RELEASE
static PHP_MSHUTDOWN_FUNCTION(phalcon)
{
	zephir_deinitialize_memory(TSRMLS_C);
	UNREGISTER_INI_ENTRIES();
	return SUCCESS;
}
#endif

/**
 * Initialize globals on each request or each thread started
 */
static void php_zephir_init_globals(zend_phalcon_globals *phalcon_globals TSRMLS_DC)
{
	phalcon_globals->initialized = 0;

	/* Memory options */
	phalcon_globals->active_memory = NULL;

	/* Virtual Symbol Tables */
	phalcon_globals->active_symbol_table = NULL;

	/* Cache Enabled */
	phalcon_globals->cache_enabled = 1;

	/* Recursive Lock */
	phalcon_globals->recursive_lock = 0;

	/* Static cache */
	memset(phalcon_globals->scache, '\0', sizeof(zephir_fcall_cache_entry*) * ZEPHIR_MAX_CACHE_SLOTS);




}

/**
 * Initialize globals only on each thread started
 */
static void php_zephir_init_module_globals(zend_phalcon_globals *phalcon_globals TSRMLS_DC)
{

}

static PHP_RINIT_FUNCTION(phalcon)
{

	zend_phalcon_globals *phalcon_globals_ptr;
#ifdef ZTS
	tsrm_ls = ts_resource(0);
#endif
	phalcon_globals_ptr = ZEPHIR_VGLOBAL;

	php_zephir_init_globals(phalcon_globals_ptr TSRMLS_CC);
	zephir_initialize_memory(phalcon_globals_ptr TSRMLS_CC);


	return SUCCESS;
}

static PHP_RSHUTDOWN_FUNCTION(phalcon)
{
	
	zephir_deinitialize_memory(TSRMLS_C);
	return SUCCESS;
}

static PHP_MINFO_FUNCTION(phalcon)
{
	php_info_print_box_start(0);
	php_printf("%s", PHP_PHALCON_DESCRIPTION);
	php_info_print_box_end();

	php_info_print_table_start();
	php_info_print_table_header(2, PHP_PHALCON_NAME, "enabled");
	php_info_print_table_row(2, "Author", PHP_PHALCON_AUTHOR);
	php_info_print_table_row(2, "Version", PHP_PHALCON_VERSION);
	php_info_print_table_row(2, "Build Date", __DATE__ " " __TIME__ );
	php_info_print_table_row(2, "Powered by Zephir", "Version " PHP_PHALCON_ZEPVERSION);
	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();
}

static PHP_GINIT_FUNCTION(phalcon)
{
	php_zephir_init_globals(phalcon_globals TSRMLS_CC);
	php_zephir_init_module_globals(phalcon_globals TSRMLS_CC);
}

static PHP_GSHUTDOWN_FUNCTION(phalcon)
{

}


zend_function_entry php_phalcon_functions[] = {
ZEND_FE_END

};

zend_module_entry phalcon_module_entry = {
	STANDARD_MODULE_HEADER_EX,
	NULL,
	NULL,
	PHP_PHALCON_EXTNAME,
	php_phalcon_functions,
	PHP_MINIT(phalcon),
#ifndef ZEPHIR_RELEASE
	PHP_MSHUTDOWN(phalcon),
#else
	NULL,
#endif
	PHP_RINIT(phalcon),
	PHP_RSHUTDOWN(phalcon),
	PHP_MINFO(phalcon),
	PHP_PHALCON_VERSION,
	ZEND_MODULE_GLOBALS(phalcon),
	PHP_GINIT(phalcon),
	PHP_GSHUTDOWN(phalcon),
	NULL,
	STANDARD_MODULE_PROPERTIES_EX
};

#ifdef COMPILE_DL_PHALCON
ZEND_GET_MODULE(phalcon)
#endif
