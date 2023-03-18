
import Card from '../../../view/site/common/card/card';
import CardRow from '../../../view/site/common/card/cardRow';

export default {
  data:function () {
    return {
      fandaiurl:'',
      hosturl:'',
      airenge:'',
      apikey:'',
      aicid:0,
      aiuid:0,
      aiusername:'',
      aipassword:'',
      callai:false
    }
  },
  created(){
    this.annexSet()
  },
  methods:{
    annexSet() {
      this.appFetch({
        url: 'forum',
        method: 'get',
        data: {}
      }).then(res => {
        if (res.errors) {
          this.$message.error(res.errors[0].code);
        } else {
          this.fandaiurl = res.readdata._data.set_chatgpt.fandaiurl;
          this.hosturl = res.readdata._data.set_chatgpt.hosturl;
          this.aicid = res.readdata._data.set_chatgpt.aicid;
          this.aiuid = res.readdata._data.set_chatgpt.aiuid;
          this.aiusername = res.readdata._data.set_chatgpt.aiusername;
          this.aipassword = res.readdata._data.set_chatgpt.aipassword;
          this.callai = res.readdata._data.set_chatgpt.callai;
          this.airenge = res.readdata._data.set_chatgpt.airenge;
          this.apikey = res.readdata._data.set_chatgpt.apikey;
        }
      })
    },

    submi() {
      var fandaiurl = this.fandaiurl;
      var hosturl = this.hosturl;
      var aicid = this.aicid;
      var aiuid = this.aiuid;
      var aiusername = this.aiusername;
      var aipassword = this.aipassword;
      var callai = this.callai;
      var airenge = this.airenge;
      var apikey = this.apikey;

      if (!fandaiurl) {
        this.$message.error('请配置反代');
        return
      }

      if (!hosturl) {
        this.$message.error('请配置自己的网站地址');
        return
      }

      if (!apikey) {
        this.$message.error('请配置OpenApiKey');
        return
      }

      if (!aicid) {
        this.$message.error('请配置分类id');
        return
      }

      if (!aiuid) {
        this.$message.error('请配置AI的uid');
        return
      }

      if (!aiusername) {
        this.$message.error('请配置AI的用户名');
        return
      }

      if (!aipassword) {
        this.$message.error('请配置AI的用户密码');
        return
      }

      if (!airenge) {
        this.$message.error('请配置AI的人格');
        return
      }

      this.appFetch({
        url: 'settings',
        method: 'post',
        data: {
          "data": [
            {
              "attributes": {
                "key": 'fandaiurl',
                "value": this.fandaiurl,
                "tag": "chatgpt"
              }
            },
            {
              "attributes": {
                "key": 'hosturl',
                "value": this.hosturl,
                "tag": "chatgpt"
              }
            },
            {
              "attributes": {
                "key": 'aicid',
                "value": this.aicid,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'aiuid',
                "value": this.aiuid,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'aiusername',
                "value": this.aiusername,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'aipassword',
                "value": this.aipassword,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'callai',
                "value": this.callai,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'apikey',
                "value": this.apikey,
                "tag": "chatgpt",
              }
            },
            {
              "attributes": {
                "key": 'airenge',
                "value": this.airenge,
                "tag": "chatgpt",
              }
            },
          ]
        }
      }).then(data => {
        if (data.errors) {
          this.$message.error(data.errors[0].code);
        } else {
          this.$message({ message: '提交成功', type: 'success' });
          this.annexSet()  //提交成功后调取新数据
        }
      }).catch(error => {
      })
    }
  },
  components:{
    Card,
    CardRow
  }
}
