//
function ciniki_surveys_main() {
    this.surveyStatuses = {
        '5':'Creating',
        '10':'Active',
        '40':'Closed',
        '60':'Deleted',
        };
    this.init = function() {
        this.menu = new M.panel('Surveys',
            'ciniki_surveys_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.surveys.main.menu');
        this.menu.sections = {
            '5':{'label':'Creating', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
            '10':{'label':'Active', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
            '40':{'label':'Closed', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
            '60':{'label':'Deleted', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
        };
        this.menu.sectionData = function(s) {
            return this.data[s];
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( d.survey.date_expires == '' ) {
                return '<span class="maintext">' + d.survey.name + '</span><span class="subtext">Never expires</span>';
            } else {
                return '<span class="maintext">' + d.survey.name + '</span><span class="subtext">expires ' + d.survey.date_expires + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_surveys_main.showSurvey(\'M.ciniki_surveys_main.showMenu();\',\'' + d.survey.id + '\');';
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_surveys_main.showEdit(\'M.ciniki_surveys_main.showMenu();\',\'0\');');
        this.menu.addClose('Back');

        //
        // The survey panel
        //
        this.survey = new M.panel('Survey',
            'ciniki_surveys_main', 'survey',
            'mc', 'medium', 'sectioned', 'ciniki.surveys.main.survey');
        this.survey.survey_id = 0;
        this.survey.sections = {
            'details':{'label':'', 'list':{ 
                'status_text':{'label':'Status'},
                'name':{'label':'Name'},
                'date_expires':{'label':'Expires'},
            }},
            'instructions':{'label':'Instructions', 'type':'htmlcontent'},
            '_buttons':{'label':'', 'visible':'yes', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_surveys_main.showEdit(\'M.ciniki_surveys_main.showSurvey();\',M.ciniki_surveys_main.survey.survey_id);'},
            }},
            'questions':{'label':'', 'type':'simplegrid', 'num_cols':2,
                'headerValues':null,
                'cellClasses':['', ''],
                'addTxt':'Add Question',
                'addFn':'M.ciniki_surveys_main.showEditQuestion(\'M.ciniki_surveys_main.showSurvey();\',0,M.ciniki_surveys_main.survey.survey_id);',
                },
            'stats':{'label':'Statistics', 'visible':'no', 'list':{
                'total_invites':{'label':'Total Invites'},
                'invites_sent':{'label':'Invites Sent'},
                'invites_seen':{'label':'Invites Seen'},
                'invites_answered':{'label':'Invites Answered'},
                }},
//          'stats':{'label':'', 'visible':'no', 'type':'simplegrid', 'num_cols':2,
//              'headerValues':null,
//              'cellClasses':['label', ''],
//              },
            '_buttons2':{'label':'', 'visible':'no', 'buttons':{
                'download':{'label':'Download Answers', 'fn':'M.ciniki_surveys_main.downloadAnswers(\'M.ciniki_surveys_main.showSurvey();\',M.ciniki_surveys_main.survey.survey_id);'},
            }},
        };
        this.survey.listLabel = function(s, i, d) {
            switch (s) {
                case 'details': return d.label;
                case 'stats': return d.label;
            }
        };
        this.survey.listValue = function(s, i, d) {
            if( s == 'stats' ) {
                switch(i) {
                    case 'total_invites': return this.data.stats.total_invites;
                    case 'invites_sent': 
                        var percentage = '';
                        if( this.data.stats != null && this.data.stats.total_invites != null && this.data.stats.total_invites > 0 ) {
                            percentage = ' <span class="subdue">[' + Math.round((this.data.stats.invites_sent/this.data.stats.total_invites)*100) + '%]</span>';
                        }
                        return this.data.stats.invites_sent + percentage;
                    case 'invites_seen': 
                        var percentage = '';
                        if( this.data.stats != null && this.data.stats.total_invites != null && this.data.stats.total_invites > 0 ) {
                            percentage = ' <span class="subdue">[' + Math.round((this.data.stats.invites_answered/this.data.stats.total_invites)*100) + '%]</span>';
                        }
                        return this.data.stats.invites_seen + percentage;
                    case 'invites_answered': 
                        var percentage = '';
                        if( this.data.stats != null && this.data.stats.total_invites != null && this.data.stats.total_invites > 0 ) {
                            percentage = ' <span class="subdue">[' + Math.round((this.data.stats.invites_answered/this.data.stats.total_invites)*100) + '%]</span>';
                        }
                        return this.data.stats.invites_answered + percentage;
                }
            }
            return this.data[i];
        };
        this.survey.sectionData = function(s) {
            if( s == 'details' || s == 'stats' ) { return this.sections[s].list; }
            if( s == 'instructions' ) { return this.data['instructions-html']; }
            return this.data[s];
        };
        this.survey.cellValue = function(s, i, j, d) {
            if( s == 'questions' ) {
                switch (j) {
                    case 0: return d.question.number;
                    case 1: return d.question.question;
                }
            }
        };
        this.survey.rowFn = function(s, i, d) {
            if( this.data['status'] >= 10 ) {
                return 'M.ciniki_surveys_main.showQuestion(\'M.ciniki_surveys_main.showSurvey();\',\'' + d.question.id + '\',M.ciniki_surveys_main.survey.data.questions);';
            } 
            return 'M.ciniki_surveys_main.showEditQuestion(\'M.ciniki_surveys_main.showSurvey();\',\'' + d.question.id + '\',M.ciniki_surveys_main.survey.survey_id);';
        };
        this.survey.addButton('edit', 'Edit', 'M.ciniki_surveys_main.showEdit(\'M.ciniki_surveys_main.showSurvey();\',M.ciniki_surveys_main.survey.survey_id);');
        this.survey.addClose('Back');

        //
        // The edit survey panel
        //
        this.edit = new M.panel('Survey',
            'ciniki_surveys_main', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.surveys.main.edit');
        this.edit.survey_id = 0;
        this.edit.data = {};
        this.edit.default_data = {'status':'5'};
        this.edit.sections = {
            'details':{'label':'', 'fields':{   
                'status':{'label':'Status', 'type':'toggle', 'toggles':this.surveyStatuses},
                'name':{'label':'Name', 'type':'text'},
                'date_expires':{'label':'Expires', 'type':'date'},
            }},
            '_instructions':{'label':'Instructions', 'fields':{
                'instructions':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_surveys_main.saveSurvey();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i];
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.surveys.surveyHistory', 'args':{'tnid':M.curTenantID, 
                'survey_id':M.ciniki_surveys_main.edit.survey_id, 'field':i}};
        };

        this.edit.addButton('save', 'Save', 'M.ciniki_surveys_main.saveSurvey();');
        this.edit.addClose('Cancel');

        //
        // The question panel
        //
        this.question = new M.panel('Survey Question',
            'ciniki_surveys_main', 'question',
            'mc', 'medium', 'sectioned', 'ciniki.surveys.main.survey');
        this.question.question_id = 0;
        this.question.prev_question_id = 0;
        this.question.next_question_id = 0;
        this.question.sections = {
            'details':{'label':'', 'list':{ 
                'number':{'label':'Number'},
                'question':{'label':'Question'},
            }},
//          'stats':{'label':'Statistics', 'list':{ 
//              'response_count':{'label':'Response/Invites'},
//              'response_rate':{'label':'Response Rate'},
//          }},
            'top_answers':{'label':'Top Answers', 'type':'simplegrid',
                'num_cols':2,
                'headerValues':['Number', 'Answer'],
                'cellClasses':['',''],
            },
            'answers':{'label':'Answers', 'type':'simplegrid', 'num_cols':3,
                'sortable':'yes', 
                'headerValues':['Customer', 'Date', 'Answer'],
                'cellClasses':['', ''],
                'sortTypes':['text', 'date', 'text'],
                },
            '_buttons2':{'label':'', 'visible':'no', 'buttons':{
                'download':{'label':'Download Answers', 'fn':'M.ciniki_surveys_main.downloadAnswers(\'M.ciniki_surveys_main.showSurvey();\',M.ciniki_surveys_main.survey.survey_id);'},
            }},
        };
        this.question.listLabel = function(s, i, d) {
            switch (s) {
                case 'details': return d.label;
            }
        };
        this.question.listValue = function(s, i, d) {
            return this.data[i];
        };
        this.question.sectionData = function(s) {
            if( s == 'details' ) {
                return this.sections[s].list;
            }
            return this.data[s];
        };
        this.question.cellValue = function(s, i, j, d) {
            if(s == 'top_answers') {
                switch (j) {
                    case 0: var percentage = '';
                        if( this.data.stats != null && this.data.stats.answer_count != null ) {
                            percentage = ' <span class="subdue">[' + Math.round((d.answer.answer_count/this.data.stats.answer_count)*100) + '%]</span>';
                        }
                        return d.answer.answer_count + percentage;
                    case 1: return d.answer.answer;
                }
            } else if(s == 'answers') {
                switch (j) {
                    case 0: return d.answer.customer_name;
                    case 1: return d.answer.date_answered;
                    case 2: return d.answer.answer;
                }
            }
        };
        this.question.prevButtonFn = function() {
            if( this.prev_question_id > 0 ) {
                return 'M.ciniki_surveys_main.showQuestion(null,\'' + this.prev_question_id + '\');';
            }
            return null;
        };
        this.question.nextButtonFn = function() {
            if( this.next_question_id > 0 ) {
                return 'M.ciniki_surveys_main.showQuestion(null,\'' + this.next_question_id + '\');';
            }
            return null;
        };
        this.question.addButton('edit', 'Edit', 'M.ciniki_surveys_main.showEditQuestion(\'M.ciniki_surveys_main.showQuestion();\',M.ciniki_surveys_main.question.question_id);');
        this.question.addButton('next', 'Next');
        this.question.addClose('Back');
        this.question.addLeftButton('prev', 'Prev');

        //
        // The edit question panel
        //
        this.editquestion = new M.panel('Survey Question',
            'ciniki_surveys_main', 'editquestion',
            'mc', 'medium', 'sectioned', 'ciniki.surveys.main.editquestion');
        this.editquestion.survey_id = 0;
        this.editquestion.question_id = 0;
        this.editquestion.data = {};
        this.editquestion.default_data = {'status':'10'};
        this.editquestion.sections = {
            'details':{'label':'', 'fields':{   
                'number':{'label':'Number', 'type':'text', 'size':'small'},
                'question':{'label':'Question', 'type':'text'},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_surveys_main.saveQuestion();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_surveys_main.deleteQuestion();'},
            }},
        };
        this.editquestion.fieldValue = function(s, i, d) { 
            return this.data[i];
        };
        this.editquestion.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.surveys.questionHistory', 'args':{'tnid':M.curTenantID, 
                'question_id':M.ciniki_surveys_main.editquestion.question_id, 'field':i}};
        };
        this.editquestion.addButton('save', 'Save', 'M.ciniki_surveys_main.saveQuestion();');
        this.editquestion.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_surveys_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        if( args.survey_id != null ) {
            this.showSurvey(cb, args.survey_id);
        } else {
            this.showMenu(cb);
        }
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMenu = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.surveys.surveyListByStatus', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_surveys_main.showMenuFinish(cb, rsp);
        });
    }

    this.showMenuFinish = function(cb, rsp) {
        this.menu.data = {};
        this.menu.sections['5'].visible = 'no';
        this.menu.sections['10'].visible = 'no';
        this.menu.sections['40'].visible = 'no';
        this.menu.sections['60'].visible = 'no';
        for(i in rsp.statuses) {
            if( this.menu.sections[rsp.statuses[i].status.id] != null ) {
                this.menu.data[rsp.statuses[i].status.id] = rsp.statuses[i].status.surveys;
                this.menu.sections[rsp.statuses[i].status.id].visible = 'yes';
            }
        }
        if( rsp.statuses.length == 0 ) {
            this.menu.sections['5'].visible = 'yes';
            this.menu.sections['5'].addTxt = 'Start new survey';
            this.menu.sections['5'].addFn = 'M.ciniki_surveys_main.showEdit(\'M.ciniki_surveys_main.showMenu();\',\'0\');';
        } else {
            this.menu.sections['5'].addTxt = '';
            this.menu.sections['5'].addFn = '';
        }
        this.menu.refresh();
        this.menu.show(cb);
    };

    this.showSurvey = function(cb, sid) {
        if( sid != null ) {
            this.survey.survey_id = sid;
        }
        var rsp = M.api.getJSONCb('ciniki.surveys.surveyGet', 
            {'tnid':M.curTenantID, 'survey_id':this.survey.survey_id, 'questions':'yes', 'stats':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_surveys_main.survey;
                p.data = rsp.survey;
                if( p.data.status != null && p.data.status >= 10 ) {
                    p.sections.stats.visible = 'yes';
                    p.sections._buttons.visible = 'no';
        //          p.sections._buttons2.visible = 'yes';
                } else {
                    p.sections.stats.visible = 'no';
                    p.sections._buttons.visible = 'yes';
                    p.sections._buttons2.visible = 'no';
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.showEdit = function(cb, sid) {
        if( sid != null ) {
            this.edit.survey_id = sid;
        }
        if( this.edit.survey_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.surveys.surveyGet', 
                {'tnid':M.curTenantID, 'survey_id':this.edit.survey_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_surveys_main.edit;
                    p.data = rsp.survey;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = this.edit.default_data;
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveSurvey = function() {
        if( this.edit.survey_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.surveys.surveyUpdate', 
                    {'tnid':M.curTenantID, 'survey_id':this.edit.survey_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_surveys_main.edit.close();
                    });
            }
        } else {
            var c = this.edit.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.surveys.surveyAdd', 
                {'tnid':M.curTenantID, 'status':'5'}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_surveys_main.showSurvey(M.ciniki_surveys_main.edit.cb, rsp.id);
                });
        }
    }

    this.showQuestion = function(cb, qid, list) {
        if( qid != null ) {
            this.question.question_id = qid;
        }
        if( list != null ) {
            this.question.list = list;
        }
        if( this.question.question_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.surveys.questionGet', 
                {'tnid':M.curTenantID, 'question_id':this.question.question_id,
                'stats':'yes', 'top_answers':5, 'answers':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_surveys_main.question;
                    p.data = rsp.question;
                    // Setup next/prev buttons
                    p.prev_question_id = 0;
                    p.next_question_id = 0;
                    if( p.list != null ) {
                        for(i in p.list) {
                            if( p.next_question_id == -1 ) {
                                p.next_question_id = p.list[i].question.id;
                                break;
                            } else if( p.list[i].question.id == p.question_id ) {
                                // Flag to pickup next question
                                p.next_question_id = -1;
                            } else {
                                p.prev_question_id = p.list[i].question.id;
                            }
                        }
                    }
                    p.refresh();
                    p.show(cb);
                });
        }
    };

    this.showEditQuestion = function(cb, qid, sid) {
        if( qid != null ) {
            this.editquestion.question_id = qid;
            this.editquestion.survey_id = sid;
        }
        if( this.editquestion.question_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.surveys.questionGet', 
                {'tnid':M.curTenantID, 'question_id':this.editquestion.question_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_surveys_main.editquestion;
                    p.data = rsp.question;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.editquestion.reset();
            this.editquestion.data = this.editquestion.default_data;
            this.editquestion.refresh();
            this.editquestion.show(cb);
        }
    };

    this.saveQuestion = function() {
        if( this.editquestion.question_id > 0 ) {
            var c = this.editquestion.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.surveys.questionUpdate', 
                    {'tnid':M.curTenantID, 'question_id':this.editquestion.question_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_surveys_main.editquestion.close();
                    });
            } else {
                this.editquestion.close();
            }
        } else {
            var c = this.editquestion.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.surveys.questionAdd', 
                {'tnid':M.curTenantID, 'survey_id':this.editquestion.survey_id, 
                    'type':10, 'status':10}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_surveys_main.editquestion.close();
                    });
        }
    };

    this.deleteQuestion = function() {
        if( confirm('Are you sure you want to delete this question?') ) {
            var rsp = M.api.getJSONCb('ciniki.surveys.questionDelete', {'tnid':M.curTenantID, 
                'question_id':this.editquestion.question_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_surveys_main.editquestion.close();
                });
        }
    };
}
