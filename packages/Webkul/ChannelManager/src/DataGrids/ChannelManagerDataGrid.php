<?php
namespace Webkul\ChannelManager\DataGrids;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\DataGrid\DataGrid;

class ChannelManagerDataGrid extends DataGrid
{
    protected $index = 'id';
    protected $sortOrder = 'desc';

    /**
     * Prepare query builder using DB facade and return it.
     */
    public function prepareQueryBuilder(): Builder
    {
        Log::info('--- ChannelManagerDataGrid: prepareQueryBuilder START (Using DB::table) ---');

        $queryBuilder = DB::table('channels') // <<<=== DÙNG DB::TABLE
            ->select( // <<<=== CHỌN CÁC CỘT GỐC TỪ BẢNG 'channels'
                'channels.id', // Chọn ID gốc
                'channels.name',
                'channels.type',
                'channels.status',
                'channels.created_at'
                // Không cần alias 'channel_id' nữa trừ khi bạn join
            );

        // Thêm các bộ lọc nếu cần, tương tự LeadDataGrid dùng $this->addFilter()
        $this->addFilter('id', 'channels.id'); // Ví dụ
        $this->addFilter('name', 'channels.name');
        $this->addFilter('type', 'channels.type');
        $this->addFilter('status', 'channels.status');
        $this->addFilter('created_at', 'channels.created_at');

        Log::info('--- ChannelManagerDataGrid: prepareQueryBuilder END (Returning DB Query Builder) ---');

        return $queryBuilder; // <<<=== RETURN QUERY BUILDER THAY VÌ SET
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'Channel ID',
            'type'       => 'integer', // <<<=== THAY THÀNH 'integer' GIỐNG LEAD
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => __('channelmanager::app.datagrid.name'),
            'type'       => 'string',
            'searchable' => true, // Có thể bật searchable nếu DB hỗ trợ
            'sortable'   => true,
            'filterable' => true,
        ]);

         $this->addColumn([
             'index'      => 'type',
             'label'      => __('channelmanager::app.datagrid.type'),
             'type'       => 'string',
             'sortable'   => true,
             'filterable' => true,
             'filterable_type' => 'dropdown', // Giữ lại dropdown filter
             'filterable_options' => [
                 ['label' => __('channelmanager::app.create.type_messenger'), 'value' => 'messenger'],
                 ['label' => __('channelmanager::app.create.type_channex'), 'value' => 'channex'],
              ]
         ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => 'Status',
            'type'       => 'boolean', // Kiểu boolean vẫn OK
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                if ($row->status == 1) {
                    return '<span class="badge badge-md badge-success">' . __('channelmanager::app.datagrid.active') . '</span>';
                } else {
                    return '<span class="badge badge-md badge-danger">' . __('channelmanager::app.datagrid.inactive') . '</span>';
                }
            },
        ]);

        $this->addColumn([
            'index'           => 'created_at',
            'label'           => 'Create date',
            'type'            => 'date', // <<<=== THAY THÀNH 'date' GIỐNG LEAD (HOẶC datetime nếu cần cả giờ)
            'searchable'      => false,
            'sortable'        => true,
            'filterable'      => true,
            'filterable_type' => 'date_range', // <<<=== GIỮ LẠI NẾU TYPE LÀ 'date' HOẶC 'datetime'
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void // Thêm void giống Lead
    {
         // Giữ nguyên phần actions, đảm bảo dùng $row->id
         $this->addAction([
             'icon'   => 'icon-view',
             'title'  => __('admin::app.datagrid.edit'),
             'method' => 'GET',
             'url'    => fn ($row) => route('admin.channelmanager.edit', $row->id), // <<<=== DÙNG $row->id
         ]);

         $this->addAction([
             'icon'   => 'icon-delete',
             'title'  => __('admin::app.datagrid.delete'),
             'method' => 'DELETE',
             'url'    => fn ($row) => route('admin.channelmanager.delete', $row->id), // <<<=== DÙNG $row->id
             'confirm_text' => __('ui::app.datagrid.massaction.delete', ['resource' => __('channelmanager::app.channel_resource')]),
         ]);
    }

    /**
     * Prepare mass actions.
     */
    // public function prepareMassActions(): void // Thêm void giống Lead
    // {
    //     // Giữ nguyên phần mass actions
    //      $this->addMassAction([
    //          'icon'   => 'icon-delete',
    //          'title'  => __('admin::app.datagrid.delete'),
    //          'method' => 'DELETE', // Hoặc POST nếu Controller dùng POST
    //          'url'    => route('admin.channelmanager.mass_delete'),
    //          'confirm_text' => __('ui::app.datagrid.massaction.delete', ['resource' => __('channelmanager::app.channel_resource')]),
    //      ]);

    //      $this->addMassAction([
    //         'icon'    => 'icon-edit',
    //         'title'   => __('channelmanager::app.datagrid.update_status'),
    //         'method'  => 'PUT', // Hoặc POST nếu Controller dùng POST
    //         'url'     => route('admin.channelmanager.mass_update'),
    //         'options' => [
    //             ['label' => __('channelmanager::app.datagrid.active'), 'value' => 1],
    //             ['label' => __('channelmanager::app.datagrid.inactive'), 'value' => 0],
    //         ],
    //     ]);
    // }
}
