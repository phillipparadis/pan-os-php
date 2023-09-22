<?php
/**
 * ISC License
 *
 * Copyright (c) 2014-2018, Palo Alto Networks Inc.
 * Copyright (c) 2019, Palo Alto Networks Inc.
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */



RoutingCallContext::$supportedActions['display'] = Array(
    'name' => 'display',
    'MainFunction' => function ( RoutingCallContext $context )
    {
        $virtualRouter = $context->object;
        PH::print_stdout("     * ".get_class($virtualRouter)." '{$virtualRouter->name()}'" );
        PH::$JSON_TMP['sub']['object'][$virtualRouter->name()]['name'] = $virtualRouter->name();
        PH::$JSON_TMP['sub']['object'][$virtualRouter->name()]['type'] = get_class($virtualRouter);


        foreach( $virtualRouter->staticRoutes() as $staticRoute )
        {
            $text = $staticRoute->display( $virtualRouter, true );
            PH::print_stdout( $text );
        }

        PH::print_stdout();
    },

    //Todo: display routes to zone / Interface IP
);

