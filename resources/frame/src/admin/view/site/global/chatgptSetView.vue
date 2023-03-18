<template>
  <div class="chatgpt-set-box">
    <Card header="反代地址：">
      <CardRow description="推荐自建反代">
        <el-input v-model="fandaiurl"></el-input>
        <template #tail>
          <span style="color: #336699"><a href="联系微信Naruto1986519" target="_blank">反代搭建</a></span>
        </template>
      </CardRow>
    </Card>

    <Card header="openApiKey：">
      <CardRow description="openApiKey">
        <el-input v-model="apikey"></el-input>
        <template #tail>
          <span style="color: #336699"><a href="联系微信Naruto1986519" target="_blank">申请openai账号(付费)</a></span>
        </template>
      </CardRow>
    </Card>

    <Card header="网站地址：">
      <CardRow description="自己网站的地址 如https://www.baidu.com">
        <el-input v-model="hosturl"></el-input>
      </CardRow>
    </Card>

    <Card header="ChatGpt回复分类ID：">
      <CardRow description="多个请用,隔开，例如 1,2,3,4">
        <el-input v-model="aicid"></el-input>
      </CardRow>
    </Card>

    <Card header="是否开启@自动回复">
      <CardRow class="card-pay" description="是否开启@自动回复。">
        <el-switch
          v-model="callai"
          active-color="#336699"
          inactive-color="#bbbbbb"
        >
        </el-switch>
      </CardRow>
    </Card>

    <Card header="ChatGpt用户UID：">
      <CardRow description="ChatGpt用户UID">
        <el-input v-model="aiuid"></el-input>
      </CardRow>
    </Card>

    <Card header="ChatGpt用户名：">
      <CardRow description="ChatGpt用户名">
        <el-input v-model="aiusername"></el-input>
      </CardRow>
    </Card>

    <Card header="ChatGpt用户密码：">
      <CardRow description="ChatGpt用户密码">
        <el-input v-model="aipassword"></el-input>
      </CardRow>
    </Card>

    <Card header="ChatGpt人格设置：">
      <CardRow description="设置ChatGpt人格">
        <el-input v-model="airenge"></el-input>
      </CardRow>
    </Card>

    <Card class="footer-btn">
      <el-button type="primary" size="medium" @click="submi">提交</el-button>
    </Card>
  </div>
</template>

<script>
import chatgptSetCon from "../../../controllers/site/global/chatgptSetCon";
import '../../../scss/site/module/globalStyle.scss';

export default {
  name: "chatgpt-set-view",
  ...chatgptSetCon
}
</script>

<style scoped>

</style>
