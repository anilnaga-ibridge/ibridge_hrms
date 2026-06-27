<template>
    <a-card :bordered="false" :bodyStyle="bodyStyle" class="state-widget-card">
        <CardWidget>
            <a-space>
                <CardWidgetIcon :bgColor="bgColor" class="custom-card-widget-icon">
                    <slot name="image"></slot>
                </CardWidgetIcon>
                <figcaption>
                    <slot name="description"></slot>
                </figcaption>
            </a-space>
        </CardWidget>
    </a-card>
</template>

<script>
import { CardWidget, CardWidgetIcon } from "./style";
import common from "../../../composable/common";

export default {
    props: {
        bgColor: {
            default: "#7b2a72",
        },
        bodyStyle: {
            default: {},
        },
    },
    components: {
        CardWidget,
        CardWidgetIcon,
    },
    setup(props, { emit }) {
        const { themeMode } = common();

        return {
            themeMode,
        };
    },
};
</script>

<style>
.state-widget-card {
    border-radius: 18px !important;
    background: #f0f2f5 !important;
    box-shadow: 7px 7px 14px #d1d9e6, -7px -7px 14px #ffffff !important;
    border: none !important;
    transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
}
.state-widget-card:hover {
    box-shadow: 3px 3px 7px #d1d9e6, -3px -3px 7px #ffffff !important;
    transform: translateY(-2px);
}
.state-widget-card .ant-card-body {
    padding: 20px 24px !important;
}
</style>
