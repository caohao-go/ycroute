PHP_ARG_ENABLE(phalcon, whether to enable phalcon, [ --enable-phalcon   Enable Phalcon])

if test "$PHP_PHALCON" = "yes"; then

	

	if ! test "x" = "x"; then
		PHP_EVAL_LIBLINE(, PHALCON_SHARED_LIBADD)
	fi

	AC_DEFINE(HAVE_PHALCON, 1, [Whether you have Phalcon])
	phalcon_sources="phalcon.c kernel/main.c kernel/memory.c kernel/exception.c kernel/debug.c kernel/backtrace.c kernel/object.c kernel/array.c kernel/string.c kernel/fcall.c kernel/require.c kernel/file.c kernel/operators.c kernel/math.c kernel/concat.c kernel/variables.c kernel/filter.c kernel/iterator.c kernel/time.c kernel/exit.c phalcon/db/adapterinterface.zep.c
	phalcon/events/eventsawareinterface.zep.c
	phalcon/db/adapter.zep.c
	phalcon/db/dialectinterface.zep.c
	phalcon/exception.zep.c
	phalcon/db/adapter/pdo.zep.c
	phalcon/db/columninterface.zep.c
	phalcon/db/dialect.zep.c
	phalcon/db/indexinterface.zep.c
	phalcon/db/referenceinterface.zep.c
	phalcon/db/resultinterface.zep.c
	phalcon/events/eventinterface.zep.c
	phalcon/events/managerinterface.zep.c
	phalcon/db.zep.c
	phalcon/db/adapter/pdo/mysql.zep.c
	phalcon/db/column.zep.c
	phalcon/db/dialect/mysql.zep.c
	phalcon/db/exception.zep.c
	phalcon/db/index.zep.c
	phalcon/db/profiler.zep.c
	phalcon/db/profiler/item.zep.c
	phalcon/db/rawvalue.zep.c
	phalcon/db/reference.zep.c
	phalcon/db/result/pdo.zep.c
	phalcon/events/event.zep.c
	phalcon/events/exception.zep.c
	phalcon/events/manager.zep.c
	phalcon/kernel.zep.c "
	PHP_NEW_EXTENSION(phalcon, $phalcon_sources, $ext_shared,, )
	PHP_SUBST(PHALCON_SHARED_LIBADD)

	old_CPPFLAGS=$CPPFLAGS
	CPPFLAGS="$CPPFLAGS $INCLUDES"

	AC_CHECK_DECL(
		[HAVE_BUNDLED_PCRE],
		[
			AC_CHECK_HEADERS(
				[ext/pcre/php_pcre.h],
				[
					PHP_ADD_EXTENSION_DEP([phalcon], [pcre])
					AC_DEFINE([ZEPHIR_USE_PHP_PCRE], [1], [Whether PHP pcre extension is present at compile time])
				],
				,
				[[#include "main/php.h"]]
			)
		],
		,
		[[#include "php_config.h"]]
	)

	AC_CHECK_DECL(
		[HAVE_JSON],
		[
			AC_CHECK_HEADERS(
				[ext/json/php_json.h],
				[
					PHP_ADD_EXTENSION_DEP([phalcon], [json])
					AC_DEFINE([ZEPHIR_USE_PHP_JSON], [1], [Whether PHP json extension is present at compile time])
				],
				,
				[[#include "main/php.h"]]
			)
		],
		,
		[[#include "php_config.h"]]
	)

	CPPFLAGS=$old_CPPFLAGS

	PHP_INSTALL_HEADERS([ext/phalcon], [php_PHALCON.h])

fi
