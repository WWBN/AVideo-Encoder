/* DataTables adapter for the Encoder's existing POST table endpoints and formatters. */
function encoderDataTable(selector, config) {
    var table = $(selector);
    if (!table.length) {
        return table;
    }
    var columns = [];
    var order = [];
    table.find('thead th').each(function(index) {
        var header = $(this);
        var id = header.attr('data-column-id');
        var formatter = config.formatters[header.attr('data-formatter')];
        var column = {
            data: id === 'commands' ? null : id,
            name: id,
            defaultContent: '',
            orderable: header.attr('data-sortable') !== 'false'
        };
        if (header.attr('data-width')) {
            column.width = header.attr('data-width');
        }
        if (formatter) {
            column.render = function(data, type, row) {
                return type === 'display' ? formatter({id: id}, row) : data;
            };
        }
        if (header.attr('data-order')) {
            order.push([index, header.attr('data-order')]);
        }
        columns.push(column);
    });
    avideoDataTable(selector, $.extend({}, config.options, {
        avideoControls: config.options && config.options.avideoControls !== undefined ? config.options.avideoControls : true,
        serverSide: true,
        order: order,
        columns: columns,
        ajax: function(data, callback) {
            var sort = {};
            data.order.forEach(function(item) {
                sort[columns[item.column].name] = item.dir;
            });
            $.ajax({
                url: config.url,
                type: 'POST',
                dataType: 'json',
                data: {
                    current: data.length > 0 ? Math.floor(data.start / data.length) + 1 : 1,
                    rowCount: data.length,
                    searchPhrase: data.search.value,
                    sort: sort
                },
                success: function(json) {
                    callback({draw: data.draw, recordsTotal: json.total, recordsFiltered: json.total, data: json.rows});
                },
                error: function() {
                    callback({draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: []});
                    var message = 'Failed to load data. Please try again.';
                    avideoAlertError(typeof __ === 'function' ? __(message) : message);
                }
            });
        }
    }));
    return table;
}
