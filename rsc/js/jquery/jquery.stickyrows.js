(function($) {
    var uuid = 0;

    //http://chris-spittles.co.uk/jquery-calculate-scrollbar-width/
    function scrollbarWidth() {
        var $inner = $('<div style="width: 100%; height:200px;">test</div>'),
            $outer = $('<div style="width:200px;height:150px; position: absolute; top: 0; left: 0; visibility: hidden; overflow:hidden;"></div>').append($inner),
            inner = $inner[0],
            outer = $outer[0];
        $('body').append(outer);
        var w1 = inner.offsetWidth;
        $outer.css('overflow', 'scroll');
        var w2 = outer.clientWidth;
        $outer.remove();
        return (w1 - w2);
    }


    $.fn.stickyRows = function(options) {
        var val = [];
        var args = Array.prototype.slice.call(arguments, 1);

        if (typeof options === 'string') {
            this.each(function () {
                var instance = $.data(this, 'stickyRows');
                if (typeof instance !== 'undefined' && $.isFunction(instance[options])) {
                    var methodVal = instance[options].apply(instance, args);
                    if (methodVal !== undefined && methodVal !== instance) val.push(methodVal);
                }
                else return $.error('No such method "' + options + '" for stickyRows');
            });
        } else {
            this.each(function () {
                if (!$(this).is('.sticky-table-for-shifting, .sticky-table')) {
                    if (!$.data(this, 'stickyRows')) {
                        $.data(this, 'stickyRows', StickyRows(this, options));
                    } else {
                        $.data(this, 'stickyRows').setRowSets().calculateDimensions().redraw('init');
                    }
                }
            });
        }

        if (val.length === 0) return this;
        else if (val.length === 1) return val[0];
        else return val;

    };


    // Initialization
    function StickyRows(el, options) {
        return new StickyRows.prototype.init(el, options);
    }


    $.StickyRows = StickyRows;
    $.StickyRows.opts = {
        container: 'body',
        rows: 'thead',
        scrollWidth: 'auto',
        containersToSynchronize: false,
        performanceDebugging: false
    };


    // Functionality
    StickyRows.fn = $.StickyRows.prototype = {

        //initialize
        init: function(el, options) {
            var self = this;
            this.document = document;
            this.window = window;
            this.body = $('body');
            this.uuid = uuid++;

            this.table = {$element: $(el)};

            // current settings
            this.opts = $.extend(
                {},
                $.StickyRows.opts,
                this.table.$element.data(),
                options
            );

            if (this.opts.performanceDebugging) console.time("initialize stickyRows");

            //calculate scrollBar width
            this.opts.scrollWidth = this.opts.scrollWidth == 'auto' ? scrollbarWidth() : this.opts.scrollWidth;

            this.container = {$element: this.table.$element.closest(this.opts.container)};
            this.container.isBody = this.container.$element.is('body');
            this.container.isBodyScrollable = false;
            this.scroll = {'vertical': false, 'horizontal': false, mode: 'scrolling', shiftToRow: -1};

            this.setRowSets();
            this.setContainersToSynchronize();
            this.calculateDimensions();

            if (this.opts.performanceDebugging) console.timeEnd("initialize stickyRows");

            //onScroll functionality
            if (this.container.isBody) {
                $(this.document).off('.stickyRowsRedraw'+uuid).on('scroll.stickyRowsRedraw'+uuid, $.proxy(this.redraw, this));
            } else {
                this.container.$element.off('.stickyRowsRedraw'+uuid).on('scroll.stickyRowsRedraw'+uuid, $.proxy(this.redraw, this));
            }

            //onResize window
            $(this.window).off('.stickyRowsCalc'+uuid).on('resize.stickyRowsCalc'+uuid, $.proxy(this.calculateDimensions, this));

            if (typeof ResizeSensor != 'undefined' && !this.container.$element.is('body')) {
                //onResize container
                new ResizeSensor(this.container.$element, $.proxy(this.calculateDimensions, this));
            }

            //onScroll elements to synchronize
            if (this.containersToSynchronize) {
                this.containersToSynchronize.each(function() {
                    if ($(this).is('body')) {
                        self.container.isBodyScrollable = true;
                        $(self.document).off('.stickyRowsCalc'+uuid).on('scroll.stickyRowsCalc'+uuid, $.proxy(self.calculateDimensions, self));
                        return false;
                    }
                });
                this.containersToSynchronize.not('body').off('.stickyRowsCalc'+uuid).on('scroll.stickyRowsCalc'+uuid, $.proxy(this.calculateDimensions, this));
            }


            this.redraw('init');

            return this;

        },

        destroy: function() {

            if (this.container.isBody) {
                $(this.document).off('.stickyRowsRedraw'+uuid);
            } else {
                this.container.$element.off('.stickyRowsRedraw'+uuid);
            }
            $(this.window).off('.stickyRowsCalc'+uuid);

            if (this.containersToSynchronize) {
                this.containersToSynchronize.each(function() {
                    if ($(this).is('body')) {
                        $(self.document).off('.stickyRowsCalc'+uuid);
                        return false;
                    }
                });
                this.containersToSynchronize.off('.stickyRowsCalc'+uuid);
            }

            this.container.$element.children('div.resize-sensor').remove();

            this.stickyHead.$element.remove();

            this.body = null;
            this.container = null;
            this.containersToSynchronize = null;
            this.document = null;
            this.rowSets = null;
            this.stickyHead = null;
            this.stickyNow = null;
            this.table = null;
            this.window = null;
        },

        redraw: function(mode) {
            var isForceRedraw =  mode && mode == 'init';

            if (this.blocked) return this;
            if (this.opts.performanceDebugging) console.time("stickyRows redraw");

            this.blocked = true;

            //current container scroll
            var containerScrollTop = this.container.$element.scrollTop();
            var scrollContainerLeft = this.container.$element.scrollLeft();

            this.scroll.vertical = false;
            this.scroll.horizontal = false;

            //is vertical scrolling
            if (containerScrollTop != this.container.scroll.top || isForceRedraw) {
                this.scroll.vertical = containerScrollTop > this.container.scroll.top ? 'down' : 'up';

                this.container.scroll.top = containerScrollTop;

                if (this.table.offset.top - this.container.offset.top > containerScrollTop || this.table.offset.top - this.container.offset.top + this.table.height < containerScrollTop) {
                    if (!this.stickyHead.$element.hasClass('hidden')) {
                        this.stickyNow = [];
                        this.stickyHead.$element.addClass('hidden');
                        this.stickyHead.$table.empty();
                        this.stickyHead.$tableForShifting.empty().addClass('hidden');
                    }
                } else {
                    this.searchCurrentStickyRows();
                    this.renderStickyRows();
                }

            }

            //is horizontal scrolling
            if (scrollContainerLeft != this.container.scroll.left || isForceRedraw) {
                this.scroll.horizontal = scrollContainerLeft > this.container.scroll.left ? 'right' : 'left';
                this.container.scroll.left = scrollContainerLeft;

                this.setHorizontalOffset();
            }

            this.blocked = false;

            if (this.opts.performanceDebugging) console.timeEnd("stickyRows redraw");

            return this;
        },

        //collect all sticky rows
        setRowSets: function() {
            var self = this;
            var rows = self.opts.rows;
            self.rowSets = [];
            self.stickyNow = [];

            if ($.isFunction(rows)) {
                $.each(rows(), function(i, rowSet) {
                    self.rowSets.push($.map(rowSet.get().reverse(), function(row) {
                        return {$row: $(row)}
                    }));
                });
            } else if (rows instanceof jQuery) {
                self.rowSets.push($.map(rows.get().reverse(), function(row) {
                    return {$row: $(row)}
                }));
            } else if (typeof rows === 'string') {
                self.rowSets.push($.map(self.table.$element.children(rows).get().reverse(), function(row) {
                    return {$row: $(row)}
                }));
            } else if ($.isArray(rows)) {
                $.each(rows, function(i, selector) {
                    self.rowSets.push($.map(self.table.$element.children(selector).get().reverse(), function(row) {
                        return {$row: $(row)}
                    }));
                });
            } else {
                $.error('stickyRows.rows has incorrect format.');
            }

            return this;
        },

        //when this containers will scroll - sticky header will redraw
        setContainersToSynchronize: function() {
            var self = this;

            if (self.opts.containersToSynchronize) {
                var containersToSynchronize = self.opts.containersToSynchronize;
                self.containersToSynchronize = [];
                if ($.isFunction(containersToSynchronize)) {
                    self.containersToSynchronize = containersToSynchronize();
                } else if (containersToSynchronize instanceof jQuery) {
                    self.containersToSynchronize = containersToSynchronize;
                } else if (typeof containersToSynchronize === 'string') {
                    self.containersToSynchronize = $(containersToSynchronize);
                } else if ($.isArray(containersToSynchronize)) {
                    self.containersToSynchronize = $(containersToSynchronize.join(','));
                } else {
                    $.error('stickyRows.containersToSynchronize has incorrect format.');
                }
            }

            return this;
        },

        //calculate width & offset of elements
        calculateDimensions: function() {
            var self = this;
            var offset;

            if (this.blocked) return this;
            if (this.opts.performanceDebugging) console.time("stickyRows: calculate dimensions");

            this.blocked = true;

            //scrollable container
            this.container.offset = this.container.$element.offset();
            this.container.width = this.container.$element.outerWidth() - this.opts.scrollWidth;
            this.container.scroll = {top: this.container.$element.scrollTop(), left: this.container.$element.scrollLeft()};

            //table
            this.table.width = this.table.$element.outerWidth();
            this.table.height = this.table.$element.height();
            this.table.offset = this.table.$element.offset();

            //correction when calculate with container.scroll.top
            if (!self.container.isBody) {
                this.table.offset.top = this.table.offset.top + this.container.scroll.top;
            }

            //sticky rows
            $.each(self.rowSets, function(i, rowSet) {
                $.each(rowSet, function(j, rowObj) {
                    offset = rowObj.$row.offset();
                    rowObj.offset = {top: offset.top - self.container.offset.top + (self.container.isBody ? 0 : self.container.scroll.top), left: offset.left - self.container.offset.left};
                    rowObj.height = rowObj.$row.outerHeight();
                });
            });

            //insert sticky wrapper into DOM
            var bodyCorrection = {top: 0, left: 0};
            if (this.container.isBodyScrollable) {
                bodyCorrection = {top: this.body.scrollTop(), left: this.body.scrollLeft()};
            }
            if (!this.stickyHead) {
                var $element = $('<div/>').addClass('sticky-header hidden').css({'top': this.container.offset.top - bodyCorrection.top, 'left': this.table.offset.left + this.container.scroll.left - bodyCorrection.left, 'width': this.container.width}).insertBefore(this.table.$element);
                var $table = $('<table/>').addClass('sticky-table').addClass(this.table.$element.attr('class')).css({'width': this.table.width}).appendTo($element);
                var $tableForShifting = $table.clone().addClass('sticky-table-for-shifting hidden').appendTo($element);
                this.stickyHead = {$element: $element, $table: $table, $tableForShifting: $tableForShifting, height: 0, marginTop: 0, changed: false};
            } else {
                this.stickyHead.$element.css({'top': this.container.offset.top - bodyCorrection.top, 'left': this.table.offset.left + this.container.scroll.left - bodyCorrection.left, 'width': this.container.width});
                this.stickyHead.$table.css({'width': this.table.width});
                this.stickyHead.$tableForShifting.css({'width': this.table.width});
            }
            this.blocked = false;

            if (this.opts.performanceDebugging) console.timeEnd("stickyRows: calculate dimensions");

            return this;
        },



        //set
        // this.stickyNow - rows, that currently sticky
        // this.scroll.mode - scrolling/shifting
        // this.stickyHead.changed - is stickyNow changed in compare with previous data
        searchCurrentStickyRows: function() {
            var self = this;
            var upperBoundToScrolling = this.container.scroll.top;
            var upperBoundToShifting = this.container.scroll.top + this.stickyHead.height;
            var cnt;
            var stickyNow = [];

            if (self.scroll.vertical == 'up') {
                self.scroll.mode = 'scrolling';
                self.scroll.shiftToRow = -1;
            }

            $.each(this.rowSets, function(i, rowSet) {

                //set upperBoundToScrolling with correction to priority
                upperBoundToScrolling = self.container.scroll.top;
                cnt = i;
                while (cnt--) {
                    if (stickyNow[cnt]) {
                        upperBoundToScrolling += stickyNow[cnt].height;
                    }
                }

                $.each(rowSet, function(j, rowObj) {

                    if (!stickyNow.length || (stickyNow[i-1] && stickyNow[i-1].offset.top < rowObj.offset.top)) {

                        if (self.scroll.vertical == 'down' && rowObj.offset.top <= upperBoundToShifting && rowObj.offset.top >= upperBoundToScrolling) {
                            if (self.stickyNow[i] && self.stickyNow[i].$row != rowObj.$row && (self.scroll.mode == 'scrolling' || (self.scroll.shiftToRow == i && self.stickyNow[self.stickyNow.length - 1].$row != rowObj.$row))) {
                                if (self.scroll.mode == 'shifting') {
                                    self.stickyNow.splice(i, 1);
                                }
                                self.stickyNow.push(rowObj);
                                self.scroll.mode = 'shifting';
                                self.scroll.shiftToRow = i;
                            }
                            return false;
                        } else if (rowObj.offset.top < upperBoundToScrolling) {

                            stickyNow.push(rowObj);

                            if (!self.stickyNow[i] || self.stickyNow[i].$row != rowObj.$row) {
                                self.stickyHead.changed = true;
                                self.scroll.mode = 'scrolling';
                                self.scroll.shiftToRow = -1;
                            }
                            return false;
                        }
                    }
                });
            });

            if (this.stickyHead.changed || this.stickyNow.length != stickyNow.length) {
                if (this.scroll.mode == 'scrolling') {
                    this.stickyNow = stickyNow;
                }
                this.stickyHead.changed = true;
            }
        },

        //redraw sticky header
        renderStickyRows: function() {
            var self = this;
            var $el;
            var marginTop = 0;
            var stickyHeadHeight = 0;

            //if something changed
            if (this.stickyHead.changed) {

                this.stickyHead.$table.empty();
                self.stickyHead.$tableForShifting.empty();

                if (this.stickyNow.length) {
                    this.stickyHead.$element.removeClass('hidden');
                    $.each(this.stickyNow, function(i, el) {
                        stickyHeadHeight += el.height;
                        self.stickyHead.$table.append(self.getCloneOfRow(el));
                    });
                } else {
                    this.stickyHead.$element.addClass('hidden');
                }

                if (self.scroll.mode == 'scrolling') {
                    //if 'scrolling' mode
                    self.stickyHead.$tableForShifting.addClass('hidden');
                    this.stickyHead.height = stickyHeadHeight;

                } else {
                    //if 'shifting' mode
                    //fill and show 'tableForShifting'
                    if (self.scroll.shiftToRow) {
                        self.stickyHead.$tableForShifting.removeClass('hidden');
                        self.stickyHead.$table.children(':lt(' + self.scroll.shiftToRow + ')').clone().appendTo(self.stickyHead.$tableForShifting);
                    }
                }

                this.stickyHead.changed = false;
            }

            //set margin-top of table header in 'shifting' mode
            if (this.scroll.mode == 'shifting') {
                $el = this.stickyNow[this.stickyNow.length - 1];
                marginTop = ($el.offset.top - this.container.scroll.top) - this.stickyHead.height;
            }

            //set margin-top of table header when table scrolled to the end
            if (this.table.height + this.table.offset.top <= this.container.scroll.top + this.stickyHead.height && this.table.height + this.table.offset.top >= this.container.scroll.top) {
                marginTop = (self.table.offset.top + self.table.height + 1 - this.container.scroll.top) - this.stickyHead.height;
            }

            //set margin-top once per method. this can happen in shifting mode or when table scrolled to the end
            if (this.stickyHead.marginTop != marginTop) {
                this.stickyHead.marginTop = marginTop;
                this.stickyHead.$table.css({'margin-top': marginTop});
            }

        },

        //called when scrolled horizontal
        setHorizontalOffset: function() {
            this.stickyHead.$table.css({'margin-left': -this.container.scroll.left});
            this.stickyHead.$tableForShifting.css({'margin-left': -this.container.scroll.left});
        },

        getRowCells: function($row) {
            //FIXME something wrong here
            return $row.is('thead, tbody') ? $row.children().children() : $row.children();
        },

        //lazy implementation of $clone row
        getCloneOfRow: function(rowObj) {
            var $rowCloneTd;

            if (!rowObj.$clone) {
                rowObj.$clone = rowObj.$row.clone();
                $rowCloneTd = this.getRowCells(rowObj.$clone);

                $.each(this.getRowCells(rowObj.$row), function(i, cell) {
                    $rowCloneTd.eq(i).css('width', $(cell).width());
                });
            }
            return rowObj.$clone;
        }
    };


    StickyRows.prototype.init.prototype = StickyRows.prototype;


})(jQuery);
