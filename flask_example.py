import json

import flask
import httplib2

from apiclient import discovery
from oauth2client import client
from googleapiclient.errors import HttpError
from oauth2client.file import Storage
from flask import g

import uuid
app = flask.Flask(__name__)
app.secret_key = str(uuid.uuid4())
app.debug = False

YOUTUBE_READ_WRITE_SSL_SCOPE = "https://www.googleapis.com/auth/youtube.force-ssl"
YOUTUBE_API_SERVICE_NAME = "youtube"
YOUTUBE_API_VERSION = "v3"

@app.route('/')
def index(id = None):
    if 'credentials' in flask.session:
        return json.dumps({"message": "Authorized"})
    return json.dumps({"message": "Welcome"})

@app.route('/get/<id>')
def get(id):
    if not id:
        return json.dumps({'error': 'Please provide youtube video id like this: "/-ysh9iF8F2I".'}), 400
    storage = Storage("flask-example-oauth2.json")
    credentials = storage.get()
    if credentials is None:
        if 'credentials' in flask.session:
            credentials = client.OAuth2Credentials.from_json(flask.session['credentials'])
            if credentials.access_token_expired:
                return flask.redirect(flask.url_for('oauth2callback'))
        else:
            return flask.redirect(flask.url_for('oauth2callback'))
    elif credentials.invalid:
        return flask.redirect(flask.url_for('oauth2callback'))
    flask.session['credentials'] = credentials.to_json()
    try:
        http_auth = credentials.authorize(httplib2.Http())
        g_youtube_service = discovery.build('youtube', 'v3', http_auth)
        results = g_youtube_service.commentThreads().list(
            part="snippet",
            videoId=id,
            textFormat="plainText"
        ).execute()
    except HttpError:
        return json.dumps({"error": "Youtube video with id: %s could not be found." % id}), 404
    else:
        return json.dumps(results['items'])

@app.route('/oauth2callback')
def oauth2callback():
    storage = Storage("flask-example-oauth2.json")
    flow = client.flow_from_clientsecrets(
        'client_secrets.json',
        scope=YOUTUBE_READ_WRITE_SSL_SCOPE,
        redirect_uri=flask.url_for('oauth2callback', _external=True))
    print "Flow initiated"
    if 'code' not in flask.request.args:
        auth_uri = flow.step1_get_authorize_url()
        return flask.redirect(auth_uri)
    else:
        auth_code = flask.request.args.get('code')
        credentials = flow.step2_exchange(auth_code)
        flask.session['credentials'] = credentials.to_json()
        storage.put(credentials)
        return flask.redirect(flask.url_for('index')) 
