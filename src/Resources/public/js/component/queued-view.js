define(function(require) {
    'use strict';

    var QueuedView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var routing = require('routing');
    var moment = require('moment');
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
        _el: null,

        /**
         * @property
         */
        chart: null,

        initialize: function (options) {

            this._el = options._sourceElement.find(this.options.widget);
            this.initChart();
            setInterval(
                _.bind(function () {
                    if (this.chart === null) {
                        return null;
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
                }, this),
                5000
            );
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
