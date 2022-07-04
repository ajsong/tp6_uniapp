<template>
<view class="recharge-list">
	<view class="row" v-for="g in list" :key="g.id">
		<view class="time">
			<text><text v-html="$t('rechargelist.list.num')"></text>{{g.num}}</text>
			<label v-html="$t('rechargelist.list.time')"></label>{{g.add_time}}
		</view>
		<view>
			<view v-html="$t('rechargelist.list.status')"></view>
			<text :class="['status', 'status'+g.status]">{{getStatus(g.status)}}</text>
		</view>
		<view>
			<view v-html="$t('rechargelist.list.pic')"></view>
			<a class="pic" :style="{'background-image':'url('+g.pic+')'}" :href="g.pic" target="_blank"></a>
		</view>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			list: null,
			page: 1,
		}
	},
	onLoad() {
		this.getData()
	},
	onReachBottom() {
		if (this.list) this.page += 1
		this.getData()
	},
	methods: {
		getStatus(status) {
			return this.$t('rechargelist.list.status.'+status)
		},
		getData() {
			this.$.get('index/money/recharge_list', {page:this.page}, json => {
				let list = this.page === 1 ? [] : this.list
				list.push(...json.data)
				this.list = list
			})
		}
	}
}
</script>

<style lang="less">
.recharge-list{
	padding: 0.12rem;
	.row{
		padding:0.05rem 0.12rem 0.12rem 0.12rem; margin-bottom:0.12rem; overflow: hidden; border:0.01rem solid #3762ac; background:#1a1959; border-radius: 0.06rem; font-size: 0.11rem; color:#fff; text-align: left;
		&:after{content: ""; display: block; clear: both;}
		> view{
			line-height: 0.28rem;
			&:after{content: ""; display: block; clear: both;}
			> text{
				display: block; float: left;
				&.status{
					color:#999;
					&.status1{color:#539EE0;}
					&.status-1{color:#f00;}
				}
			}
			&.time > text{float: right;}
			view{float: left;}
			.pic{display:block; float:left; width:0.9rem; height:1.3rem; margin-top:0.07rem;  background: no-repeat center center;background-size: cover;}
		}
	}
}
</style>
