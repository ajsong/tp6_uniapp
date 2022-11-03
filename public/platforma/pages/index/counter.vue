<template>
<view class="counter">
	<view class="text">{{$t('index.side.list.7')}}</view>
	<view class="count">{{countText}}</view>
	
	<view class="title">{{$t('counter.title.0')}}</view>
	<view class="input"><input type="number" v-model="price" @input="priceInput" /></view>
	
	<view class="title">{{$t('counter.title.1')}}</view>
	<view class="slider">
		<slider @change="dayChange" :value="day_value" activeColor="#ab7eed" backgroundColor="#ffffff" block-color="#6966ea" block-size="18" show-value="true" min="1" :max="day" step="1" />
	</view>
	
	<view class="title">{{$t('counter.title.2')}}</view>
	<view class="input"><input type="number" v-model="person_reckon_income" disabled="true" /></view>
	
	<view class="title">{{$t('counter.title.3')}}</view>
	<view class="input select"><text>{{gradeText}}</text><picker @change="gradeChange" :range="grade" range-key="name"></picker></view>
	
	<view class="title">{{$t('counter.title.4')}}</view>
	<view class="input"><input type="number" v-model="team_price" @input="personPriceInput" /></view>
	
	<view class="title">{{$t('counter.title.5')}}</view>
	<view class="slider">
		<slider @change="peopleChange" :value="person_value" activeColor="#ab7eed" backgroundColor="#ffffff" block-color="#6966ea" block-size="18" show-value="true" min="1" :max="people" step="1" />
	</view>
	
	<view class="title">{{$t('counter.title.6')}}</view>
	<view class="input"><input type="number" v-model="team_reckon_income" disabled="true" /></view>
	
	<!-- <view class="btn" @click="submit">{{$t('counter.submit')}}</view> -->
</view>
</template>

<script>
export default {
	data() {
		return {
			countText: '',
			
			grade: null,
			day: 1,
			people: 1,
			percent: 0,
			
			
			price: '',
			day_value: 1,
			person_reckon_income: '',
			
			gradeText: '',
			team_level_percent: 0,
			team_price: '',
			person_value: 1,
			team_reckon_income: '',
		}
	},
	onLoad() {
		this.countText = this.$t('counter.count')
		this.$.get('/index/index/counter', json => {
			this.grade = json.data.grade
			this.day = json.data.day
			this.people = json.data.people
			this.percent = this.$.bcdiv(json.data.percent, 100, 3)
			
			this.day_value = Math.ceil(this.day/2)
			this.person_value = Math.ceil(this.people/2)
			this.gradeChange({
				detail: {
					value: 0
				}
			})
		})
	},
	methods: {
		priceInput(e) {
			let value = e.detail.value
			this.price = value
			this.submit()
		},
		dayChange(e) {
			let value = e.detail.value
			this.day_value = value
			this.submit()
		},
		gradeChange(e) {
			let index = e.detail.value
			this.gradeText = this.grade[index].name
			this.team_level_percent = this.$.bcdiv(this.grade[index].level_percent, 100, 3)
			this.submit()
		},
		personPriceInput(e) {
			let value = e.detail.value
			this.team_price = value
			this.submit()
		},
		peopleChange(e) {
			let value = e.detail.value
			this.person_value = value
			this.submit()
		},
		submit() {
			if (this.price.length) {
				this.person_reckon_income = this.$.bcmul(this.$.bcmul(this.price, this.day_value, 3), this.percent, 3)
			}
			if (this.team_price.length) {
				this.team_reckon_income = this.$.bcmul(this.$.bcmul(this.team_price, this.person_value, 3), this.team_level_percent, 3)
			}
		}
	}
}
</script>

<style>
.counter{color: #fff; padding:0.12rem;}
.counter .text{font-size: 0.12rem; height:0.26rem; line-height: 0.26rem; text-align: left;}
.counter .count{font-size: 0.1rem; height: 0.12rem; text-align: left;}
.counter .title{font-size: 0.12rem; height: 0.25rem; line-height: 0.25rem; margin-top: 0.12rem; text-align: left;}
.counter .input{border-radius: 0.04rem; height:0.4rem; line-height: 0.4rem; padding:0 0.15rem; background: #1a1959; position: relative;}
.counter .input input{width:100%; height:100%; color:#fff; font-size: 0.12rem; border: none; background-color: transparent;}
.counter .input picker{position:absolute; z-index: 1; left:0; top:0; width:100%; height:100%;}
.counter .input text{display: block; text-align: left; font-size: 0.12rem;}
.counter .select{padding-right:0;}
.counter .select:before{content:""; display: block; float:right; width:0.4rem; height:100%; opacity: 0.3; background:url("data:image/svg+xml;charset=utf-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20viewBox%3D%220%200%20200%20200%22%20version%3D%221.1%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20xmlns%3Axlink%3D%22http%3A%2F%2Fwww.w3.org%2F1999%2Fxlink%22%3E%3Cg%20class%3D%22transform-group%22%3E%3Cg%20transform%3D%22scale(0.1953125%2C%200.1953125)%22%3E%3Cpath%20d%3D%22M890.336%20330.912c-12.576-12.416-32.8-12.352-45.248%200.192L517.248%20661.952%20184.832%20332.512c-12.576-12.448-32.8-12.352-45.28%200.192-12.448%2012.576-12.352%2032.832%200.192%2045.28l353.312%20350.112c0.544%200.544%201.248%200.672%201.792%201.184%200.128%200.128%200.16%200.288%200.288%200.416%206.24%206.176%2014.4%209.28%2022.528%209.28%208.224%200%2016.48-3.168%2022.72-9.472l350.112-353.312C902.976%20363.616%20902.88%20343.36%20890.336%20330.912z%22%20fill%3D%22%23ffffff%22%3E%3C%2Fpath%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E") no-repeat center center; background-size: 0.14rem auto;}
.counter .slider slider{margin:0;}
.counter .slider slider >>> .uni-slider-handle-wrapper{height:8px; border-radius: 4px;}
.counter .slider slider >>> .uni-slider-thumb{box-shadow: 3px 0 4px rgba(0,0,0,0.2);}
.counter .slider slider >>> .uni-slider-thumb:before{content:""; display: block; width:10px; height:10px; margin:4px auto; border-radius: 100%; background: #fff;}
.counter .slider slider >>> .uni-slider-track{
	background:-webkit-linear-gradient(left, #ab7eed 0%, #6966ea 100%);
	background:linear-gradient(left, #ab7eed 0%, #6966ea 100%);
}
.counter .slider slider >>> .uni-slider-value{color:#fff;}
.counter .btn{height:0.44rem; line-height: 0.44rem; margin-top: 0.2rem; border-radius: 0.04rem; font-size: 0.12rem; background: #4c8fe8;}
</style>
