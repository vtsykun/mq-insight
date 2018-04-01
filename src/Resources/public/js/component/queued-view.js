define(function(require) {
    'use strict';

    var QueuedView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var routing = require('routing');
    var moment = require('moment');
    var persistentStorage = require('oroui/js/persistent-storage');
    var d3 = require('d3');
    var c3 = require('c3');

    QueuedView = BaseView.extend({
        options: {
            el: '.mq-queued',
            widget: 'div.mq-widget'
        },

        /**
         * @property {Element}
         */
        sourceElement: null,

        /**
         * @property {Element}
         */
        _el: null,

        /**
         * @property
         */
        chart: null,

        /**
         * @property {Object}
         */
        plotRefresh: null,

        initialize: function (options) {
            QueuedView.__super__.initialize.apply(this, arguments);
            this.sourceElement = options._sourceElement;
            this._el = this.sourceElement.find(this.options.widget);
            this.initChart();

            var refreshRate = persistentStorage.getItem('okvpn-mq-refresh-interval');
            if (refreshRate === null) {
                refreshRate = 10;
            }
            this.sourceElement.find('input.mq-refresh-interval').val(refreshRate);

            this.plotRefresh = setInterval(_.bind(function () {this.refreshPlot();}, this), 1000 * refreshRate);
            this.sourceElement.find('.mq-configure').on('click', _.bind(function () {
                this.onConfigureClick();
            }, this));

            this.sourceElement.find('.mq-configure-btn').on('click', _.bind(function () {
                this.onConfigureSave();
            }, this));
        },

        refreshPlot: function () {
            if (this.chart === null) {
                return null;
            }

            if ($(this.options.widget).length === 0) {
                clearInterval(this.plotRefresh);
            }

            $.post(
                routing.generate('okvpn_mq_insight_queued'),
                { },
                _.bind(function (response) {
                    var formatted = this._formatData(response.queued);
                    this._updateWidget(response);
                    this.chart.load({columns: formatted});
                }, this)
            )
        },

        onConfigureClick: function () {
            this.sourceElement.find('.mq-modal').modal('show');
        },

        onConfigureSave: function () {
            this.sourceElement.find('.mq-modal').modal('hide');
            var refreshRate = this.sourceElement.find('input.mq-refresh-interval').val();
            refreshRate = parseInt(refreshRate);
            persistentStorage.setItem('okvpn-mq-refresh-interval', refreshRate);
            if (this.plotRefresh !== null) {
                clearInterval(this.plotRefresh);
                this.plotRefresh = null;
            }

            if (refreshRate > 1) {
                this.plotRefresh = setInterval(_.bind(function () {this.refreshPlot();}, this), 1000 * refreshRate);
            }
        },

        initChart: function () {
            $.post(
                routing.generate('okvpn_mq_insight_queued'),
                {},
                _.bind(function (response) {
                    var data = {
                        bindto: this.options.el,
                        data: {
                            x: 'x',
                            xFormat: '%H:%M:%S',
                            columns: []
                        },
                        axis: {
                            x: {
                                type: 'timeseries',
                                tick: {
                                    format: '%H:%M:%S',
                                    count: 7
                                }
                            },
                            y: {
                                min: -1,
                            }
                        },
                        point: {
                            show: true
                        }
                    };

                    data.data.columns = this._formatData(response.queued);
                    this._updateWidget(response);
                    this.chart = c3.generate(data);
                }, this)
            );
        },

        /**
         * @private
         */
        _formatData: function (data) {
            var x = ['x'];
            var queued = ['queued'];
            for (var i = 0; i < data.length; ++i) {
                x.push(moment(data[i][0]*1000).format('HH:mm:ss'));
                queued.push(Math.round(data[i][1]*10)/10.);
            }

            return [x, queued];
        },

        _updateWidget: function (response) {
            var speed = 0;
            if (response.queued.length !== 0) {
                speed = Math.round(response.queued[response.queued.length - 1][1]*10)/10.
            }

            if (response.runningConsumers.length === 0) {
                speed = 0;
            }

            var html = speed + ' mes/sec. - (' + response.size + ')';
            this._el.html(html);

        }
    });

    return QueuedView;
});
