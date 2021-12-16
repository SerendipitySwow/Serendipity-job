-- auto-generated definition
create table application
(
    id          int unsigned auto_increment comment '自增ID'
        primary key,
    is_deleted  tinyint unsigned default '0'               not null comment '是否删除',
    status      tinyint unsigned default '0'               not null comment '是否审核 0:否 1:是',
    app_name    varchar(100)     default ''                not null comment '应用名称',
    app_key     char(16)         default ''                not null comment 'APP KEY',
    secret_key  char(32)         default ''                not null comment 'SECRET KEY',
    step        tinyint unsigned default '0'               not null comment '重试间隔(秒)',
    retry_total tinyint unsigned default '0'               not null comment '重试次数',
    link_url    varchar(200)     default ''                not null comment '接口地址',
    remark      varchar(255)     default ''                not null comment '备注信息',
    created_at  timestamp        default CURRENT_TIMESTAMP not null comment '创建时间',
    updated_at  timestamp        default CURRENT_TIMESTAMP not null comment '更新时间',
    constraint unq_app_key
        unique (app_key)
)
    comment '工作任务' collate = utf8mb4_unicode_ci;


create table edge
(
    edge_id      int unsigned auto_increment
        primary key,
    start_vertex int default 0 not null,
    end_vertex   int default 0 null
);


create table failed_jobs
(
    id         bigint unsigned auto_increment
        primary key,
    uuid       varchar(255)                        not null,
    connection text                                not null,
    queue      text                                not null,
    payload    longtext                            not null,
    exception  longtext                            not null,
    failed_at  timestamp default CURRENT_TIMESTAMP not null,
    constraint failed_jobs_uuid_unique
        unique (uuid)
)
    collate = utf8mb4_unicode_ci;

-- auto-generated definition
create table task
(
    id           bigint unsigned auto_increment comment '主键ID'
        primary key,
    is_deleted   tinyint unsigned         default '0'               not null comment '是否删除',
    status       tinyint unsigned         default '0'               not null comment '任务状态 0:待处理 1:处理中 2:已处理 3:已取消 4:处理失败',
    app_key      char(32) charset utf8    default ''                not null comment 'APP KEY',
    task_no      varchar(50) charset utf8 default ''                not null comment '任务编号',
    step         tinyint unsigned         default '0'               not null comment '重试间隔(秒)',
    runtime      timestamp                default CURRENT_TIMESTAMP not null comment '执行时间',
    content      longtext charset utf8                              not null comment '任务内容',
    created_at   timestamp                default CURRENT_TIMESTAMP not null comment '创建时间',
    updated_at   timestamp                default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
    timeout      int                      default -1                not null comment '任务执行时间',
    name         varchar(64)              default ''                not null comment '任务名称',
    coroutine_id bigint                   default 0                 not null comment '执行当前的任务协程ID,用于取消当前任务',
    memo         longtext                                           null comment '当任务执行出现错误时,记录错误信息',
    result       longtext                                           null comment '执行任务完成的结果',
    retry_times  int                      default 0                 not null comment '重试次数',
    server_ip    varchar(32)              default '127.0.0.1'       not null comment '运行任务的服务端IP地址',
    constraint unq_task_no
        unique (task_no)
)
    comment '任务列表';

create index idx_create_at
    on task (created_at);

create index idx_is_deleted
    on task (is_deleted, status, runtime);

create index idx_task_no
    on task (app_key, task_no);



create table task_abort
(
    id         int unsigned auto_increment comment ' 自增ID'
        primary key,
    is_deleted tinyint unsigned default '0'               not null comment ' 是否删除 ',
    task_id    bigint unsigned  default '0'               not null comment ' 任务ID ',
    status     tinyint unsigned default '0'               not null comment '拦截状态 0:未知 1:拦截成功 ',
    created_at timestamp        default CURRENT_TIMESTAMP not null comment ' 创建时间 ',
    updated_at timestamp        default CURRENT_TIMESTAMP not null comment ' 更新时间 '
)
    comment '拦截记录' collate = utf8mb4_unicode_ci;

create index idx_task_id
    on task_abort (task_id);



create index idx_task_id
    on task_abort (task_id);

-- auto-generated definition
create table task_log
(
    id         bigint unsigned                                     not null comment '主键ID'
        primary key,
    is_deleted tinyint unsigned          default '0'               not null comment '是否删除',
    task_id    bigint unsigned           default '0'               not null comment '任务ID',
    retry      tinyint unsigned          default '0'               not null comment '重试次数',
    remark     varchar(255) charset utf8 default ''                not null comment '备注信息',
    created_at timestamp                 default CURRENT_TIMESTAMP not null comment '创建时间',
    updated_at timestamp                 default CURRENT_TIMESTAMP not null comment '更新时间'
)
    comment '系统日志' collate = utf8mb4_unicode_ci;

create index idx_task_id
    on task_log (task_id);
;

create table vertex_edge
(
    id          int unsigned auto_increment
        primary key,
    workflow_id int unsigned default '0' not null,
    task_id     int unsigned default '0' null,
    pid         int unsigned default '0' null
);

-- auto-generated definition
create table workflow
(
    id         int unsigned auto_increment
        primary key,
    name       varchar(64)      default ''                not null,
    is_active  tinyint unsigned default '0'               not null comment '0 否 1 是',
    status     varchar(32)      default ''                not null comment '任务状态 0:待处理 1:处理中 2:已处理 3:已取消 4:处理失败'',',
    created_at timestamp        default CURRENT_TIMESTAMP null,
    updated_at timestamp        default CURRENT_TIMESTAMP null on update CURRENT_TIMESTAMP
)
    comment '任务流工作表';

