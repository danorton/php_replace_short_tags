# php_replace_short_tags

Copyright © 2010 Daniel Norton, Austin, Texas

LICENSE: CC-BY-SA v3.0 (http://creativecommons.org/licenses/by-sa/3.0/)

###  SYNOPSIS:
Replace short open tags in PHP source with long open tags, e.g.:
   - ``<?`` with ``<?php``
   - ``<?=`` with ``<?php echo``
   - ...

###  USAGE:

   ``php_replace_short_tags [-o|--overwrite] [-r|--recursive] [-f|--filter] [-s|--skip-echo-tags] [-q|--quiet] [-d|--debug] INOUTFILE [INOUTFILE ...]``
##### Options
- *overwrite*: Overwrite existing file(s).
- *recursive*: Search files and folders recursively.
- *filter*: Filter input files by file extension (".(inc|php|phtml|htm)$").
- *skip-echo-tags*: Ignore echo tags.
- *quiet*: ...
- *debug*: ...
