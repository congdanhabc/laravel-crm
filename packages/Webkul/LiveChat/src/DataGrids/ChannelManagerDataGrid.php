<?php

namespace Webkul\LiveChat\DataGrids; // <<=== Sửa Namespace

// use Illuminate\Database\Query\Builder; // Dùng nếu chọn cách DB::table
use Illuminate\Database\Eloquent\Builder; // Dùng nếu chọn cách Eloquent
use Illuminate\Support\Facades\DB; // Giữ lại nếu vẫn dùng cách DB::table
use Illuminate\Support\Facades\Log;
use Webkul\DataGrid\DataGrid;
use Webkul\LiveChat\Models\Channel; // <<=== Import Model nếu dùng Eloquent

class ChannelManagerDataGrid extends DataGrid // <<=== Đổi tên Class
{
    /**
     * Set index columns, ex: id.
     *
     * @var string
     */
    protected $index = 'id'; // Giữ nguyên

    /**
     * Default sort order of datagrid.
     *
     * @var string
     */
    protected $sortOrder = 'desc'; // Giữ nguyên

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    public function prepareQueryBuilder(): \Illuminate\Database\Query\Builder // Kiểu trả về có thể là 1 trong 2
    {
        // --- Cách 1: Sử dụng Eloquent Model (Khuyến nghị) ---
        Log::info('--- ChannelDataGrid: prepareQueryBuilder START (Using Eloquent) ---');
        $queryBuilder = DB::table('live_chat_channels') // Dùng DB facade để bắt đầu query
                          ->select(
                              'id', // Không cần prefix nếu chỉ query 1 bảng
                              'name',
                              'type',
                              'status',
                              'created_at'
                          );

        // Thêm các filter (giữ nguyên cách addFilter)
        $this->addFilter('id', 'id');
        $this->addFilter('name', 'name');
        $this->addFilter('type', 'type');
        $this->addFilter('status', 'status');
        $this->addFilter('created_at', 'created_at');

        Log::info('--- ChannelDataGrid: prepareQueryBuilder END (Returning Eloquent Query Builder) ---');
        return $queryBuilder;


        // --- Cách 2: Sử dụng DB::table (Như code gốc của bạn) ---
        /*
        Log::info('--- ChannelDataGrid: prepareQueryBuilder START (Using DB::table) ---');
        $queryBuilder = DB::table('live_chat_channels') // Đảm bảo tên bảng đúng
            ->select(
                'id', // Không cần prefix nếu chỉ query 1 bảng
                'name',
                'type',
                'status',
                'created_at'
            );

        $this->addFilter('id', 'id');
        $this->addFilter('name', 'name');
        $this->addFilter('type', 'type');
        $this->addFilter('status', 'status');
        $this->addFilter('created_at', 'created_at');

        Log::info('--- ChannelDataGrid: prepareQueryBuilder END (Returning DB Query Builder) ---');
        return $queryBuilder;
        */
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.datagrid.id'), // Dùng key gốc nếu có
            'type'       => 'integer', // Nên là integer
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('live_chat::app.channels.datagrid.name'), // <<=== Sửa namespace
            'type'       => 'string',
            'searchable' => true, // Bật searchable
            'sortable'   => true,
            'filterable' => true,
        ]);

         $this->addColumn([
             'index'      => 'type',
             'label'      => trans('live_chat::app.channels.datagrid.type'), // <<=== Sửa namespace
             'type'       => 'string',
             'sortable'   => true,
             'filterable' => true,
             'datetime_range' => 'dropdown',
             'filterable_options' => [ // Cập nhật options nếu cần
                 ['label' => trans('live_chat::app.channels.create.type_facebook'), 'value' => 'facebook'], // <<=== Sửa namespace
                 // ['label' => trans('live_chat::app.channels.create.type_web'), 'value' => 'web'], // Ví dụ
                 // Thêm các type khác
              ]
         ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('live_chat::app.channels.datagrid.status'), // <<=== Sửa namespace
            'type'       => 'boolean',
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                // Sử dụng class của Krayin/Tailwind nếu có
                if ($row->status == 1) {
                    return '<span class="label-active">' . trans('live_chat::app.channels.datagrid.active') . '</span>'; // <<=== Sửa namespace
                } else {
                    return '<span class="label-inactive">' . trans('live_chat::app.channels.datagrid.inactive') . '</span>'; // <<=== Sửa namespace
                }
                // Hoặc dùng class Tailwind trực tiếp:
                // $class = $row->status ? 'bg-green-100 text-green-800 dark:bg-green-500/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-500/20 dark:text-red-400';
                // $text = $row->status ? trans('live_chat::app.channels.datagrid.active') : trans('live_chat::app.channels.datagrid.inactive');
                // return "<span class='px-2 py-0.5 rounded text-xs font-medium {$class}'>{$text}</span>";
            },
        ]);

        $this->addColumn([
            'index'           => 'created_at',
            'label'           => trans('admin::app.datagrid.created_at'), // Dùng key gốc
            'type'            => 'datetime', // Nên là datetime để hiển thị cả giờ
            'sortable'        => true,
            'filterable'      => true,
            'datetime_range' => 'date_range',
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions(): void
    {
         // Chỉ thêm action nếu user có quyền
         if (bouncer()->hasPermission('live_chat.channel_manager.edit')) {
             $this->addAction([
                 'icon'   => 'icon-edit', // Sử dụng icon font của Krayin
                 'title'  => trans('admin::app.datagrid.edit'),
                 'method' => 'GET',
                 // Sử dụng tên route mới
                 'url'    => fn ($row) => route('admin.live_chat.channel_manager.edit', $row->id),
             ]);
         }

         if (bouncer()->hasPermission('live_chat.channel_manager.delete')) {
             $this->addAction([
                 'icon'   => 'icon-delete',
                 'title'  => trans('admin::app.datagrid.delete'),
                 'method' => 'DELETE',
                 // Sử dụng tên route mới
                 'url'    => fn ($row) => route('admin.live_chat.channel_manager.delete', $row->id),
                 // Sử dụng key dịch đúng
                 'confirm_text' => trans('ui::app.datagrid.massaction.delete', ['resource' => trans('live_chat::app.channels.channel_resource')]),
             ]);
         }
    }

    /**
     * Prepare mass actions. (Bỏ comment nếu bạn cần dùng)
     */
    // public function prepareMassActions(): void
    // {
    //     // Chỉ thêm nếu có quyền xóa
    //     if (bouncer()->hasPermission('live_chat.channel_manager.delete')) {
    //          $this->addMassAction([
    //              'icon'   => 'icon-delete',
    //              'title'  => trans('admin::app.datagrid.delete'),
    //              'method' => 'DELETE', // Hoặc POST nếu Controller dùng POST cho mass delete
    //              // Cần tạo route và controller method cho mass delete
    //              'url'    => route('admin.live_chat.channel_manager.mass_delete'), // <<=== TẠO ROUTE NÀY
    //              'confirm_text' => trans('ui::app.datagrid.massaction.delete', ['resource' => 'channels']),
    //          ]);
    //      }

    //      // Chỉ thêm nếu có quyền sửa
    //     if (bouncer()->hasPermission('live_chat.channel_manager.edit')) {
    //          $this->addMassAction([
    //             'icon'    => 'icon-edit', // Hoặc icon khác phù hợp
    //             'title'   => trans('live_chat::app.channels.datagrid.update_status'), // <<=== Sửa namespace
    //             'method'  => 'PUT', // Hoặc POST nếu Controller dùng POST cho mass update
    //              // Cần tạo route và controller method cho mass update
    //             'url'     => route('admin.live_chat.channel_manager.mass_update'), // <<=== TẠO ROUTE NÀY
    //             'options' => [
    //                 // Sử dụng key dịch đúng
    //                 ['label' => trans('live_chat::app.channels.datagrid.active'), 'value' => 1],
    //                 ['label' => trans('live_chat::app.channels.datagrid.inactive'), 'value' => 0],
    //             ],
    //         ]);
    //     }
    // }
}
