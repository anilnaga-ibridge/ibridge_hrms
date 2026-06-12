import { ref } from "vue";
import { useI18n } from "vue-i18n";
import common from "../../../common/composable/common";

const fields = () => {
    const url = "tasks?fields=id,xid,name,status,priority,start_date,due_date,description,project_id,project{id,xid,name},assignees,assignee_details,is_public,is_billable,task_file,task_file_url,hourly_rate,repeat_every,followers,follower_details,tags";
    const addEditUrl = "tasks";
    const { t } = useI18n();
    const { dayjs } = common();

    const initData = {
        name: "",
        project_id: undefined,
        status: "not_started",
        priority: "medium",
        start_date: dayjs().utc().format("YYYY-MM-DD"),
        due_date: undefined,
        description: "",
        assignees: [],
        is_public: false,
        is_billable: false,
        task_file: undefined,
        task_file_url: undefined,
        hourly_rate: 0,
        repeat_every: "no_repeat",
        followers: [],
        tags: [],
    };

    const columns = ref([
        {
            title: t("task.name"),
            dataIndex: "name",
        },
        {
            title: t("task.project"),
            dataIndex: "project",
        },
        {
            title: t("task.status"),
            dataIndex: "status",
        },
        {
            title: t("task.priority"),
            dataIndex: "priority",
        },
        {
            title: t("task.start_date"),
            dataIndex: "start_date",
        },
        {
            title: t("task.due_date"),
            dataIndex: "due_date",
        },
        {
            title: t("task.assignees"),
            dataIndex: "assignees",
        },
    ]);

    const filterableColumns = [
        {
            key: "name",
            value: t("task.name"),
        },
    ];

    return {
        addEditUrl,
        initData,
        columns,
        filterableColumns,
    };
};

export default fields;
