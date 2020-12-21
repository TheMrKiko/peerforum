M.core_peergrading_removepeer = {
    Y: null,
    api: M.cfg.wwwroot + '/peergrading/removepeer.php',

    init: function (Y) {
        this.Y = Y;
        Y.all('select.menuremovepeer').each(this.attach_peergrading_events, this);
    },

    attach_peergrading_events: function (selectnode) {
        selectnode.on('click', this.remove_peer_nomination, this, selectnode);
    },

    remove_peer_nomination: function (e, selectnode) {
        var theinputs = selectnode.ancestor('button');
        var thedata = [];

        var inputssize = theinputs.size();
        for (var i = 0; i < inputssize; i++) {
            if (theinputs.item(i).get("name") != "returnurl") { // Dont include return url for ajax requests.
                thedata[theinputs.item(i).get("name")] = theinputs.item(i).get("value");
            }
        }

        var scope = this;
        var itemid = thedata['itemid'];
        var peerid = thedata['peerid'];


        var cfg = {
            method: 'POST',
            data: {'itemid': itemid, 'peerid': peerid},
            on: {
                success: function (o, response) {
                    var data = Y.JSON.parse(response.responseText);

                    if (data.result) {

                        var node = scope.Y.one('favbutton' + itemid);
                        node.set('innerHTML', data.canassign);

                        var node = scope.Y.one('#menuremovepeer' + itemid);
                        node.set('innerHTML', data.canremove);

                    }
                ,
                    failure: function (o, response) {
                        alert('Error on peergrading_removepeer');
                    }
                }
            };
            this.Y.io(this.api, cfg);

        }
    };
